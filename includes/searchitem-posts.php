<?php

namespace YoutubeSearch;

use \DateTime;
use \DateTimeZone;
use YoutubeSearch\Lib\Cache;
use YoutubeSearch\Lib\ImageUpload;
use YoutubeSearch\Lib\ImageUploadFactory;

class PostSearchResultParser extends YoutubeResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {
            $dt = DateTime::createFromFormat(
                'Y-m-d\TH:i:s\Z',
                $item['snippet']['publishedAt'],
                new DateTimeZone('UTC')
            );

            array_push(
                $result,
                (object)array(
                    'title' => $item['snippet']['title'],
                    'publishedAt' => $dt,
                    'youtube_id' => $item['id']['videoId'],
                    'url' => sprintf('https://www.youtube.com/watch?v=%s', $item['id']['videoId']),
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url']
                )
            );
        }

        return (object)array(
            'data' => $result
        );

    }

}

class PostListResultParser extends YoutubeResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {

            $title = $item['snippet']['title'];
            $description = $item['snippet']['description'];
            $url = sprintf('https://www.youtube.com/watch?v=%s', $item['id']);
            $image = $item['snippet']['thumbnails']['high']['url'];
            $summary = wp_trim_words($description);
            $embed_html = $this->maybe_get_from_part(
                $item, 'player', 'embedHtml'
            );
            if ($embed_html) {
                $content = sprintf(
                    '%s<br/><br/>' .
                    '%s',
                    $description,
                    $embed_html
                );
            }
            else {
                $content = $summary;
            }

            array_push(
                $result,
                (object)array(
                    'content' => $content,
                    'summary' => $summary,
                    'image' => $image
                )
            );

        }

        return (object)array(
            'data' => $result
        );

    }

}

class SearchItemPostsHandler {

    private $cache, $image_upload, $youtube_search;

    public function __construct(YoutubeSearchHandler $youtube_search,
                                ImageUpload $image_upload) {

        $this->youtube_search = $youtube_search;
        $this->image_upload = $image_upload;

        $this->cache = new Cache('youtube-search-');

        add_action('save_post', array($this, 'maybe_insert_posts'), 10, 3);
        add_action('before_delete_post', array($this, 'clear_cache'), 10, 1);

    }

    public function maybe_insert_posts($post_ID, $post, $update) {

        $blocks = youtube_search_get_blocks($post);
        if (empty($blocks)) {
            return;
        }

        foreach ($blocks as $block) {
            $attributes = youtube_search_parse_attributes($block['attrs']);
            if (!$attributes['makePosts']) {
                continue;
            }
            $data = youtube_search_build_query($attributes);
            $data['listPart'] = 'id,snippet,player';

            try {
                $result = $this->youtube_search->search(
                    $data, new PostSearchResultParser(), new PostListResultParser()
                );
                if ($result && !empty($result->data)) {
                    foreach ($result->data as $video) {
                        $cache_key = sprintf('post-%s', $video->youtube_id);
                        $search_item_post_id = $this->cache->get($cache_key);
                        if ($search_item_post_id) {
                            continue;
                        }

                        remove_action('save_post', array($this, 'maybe_insert_posts'), 10, 3);

                        $post_vars = array(
                            'post_title' => $video->title,
                            'post_name' => sanitize_title($video->title),
                            'post_status' => 'publish',
                            'post_date' => $video->publishedAt->format('Y-m-d H:i:s'),
                            'post_content' => $video->content,
                            'post_excerpt' => $video->summary
                        );
                        if ($attributes['postsCategories']) {
                            $post_vars['post_category'] = array_map(
                                function($category) {
                                    return $category['term_id'];
                                },
                                $attributes['postsCategories']
                            );
                        }
                        if ($attributes['postsAuthor']) {
                            $post_vars['post_author'] = $attributes['postsAuthor']['id'];
                        }

                        $new_post_id = wp_insert_post($post_vars);

                        add_action('save_post', array($this, 'maybe_insert_posts'), 10, 3);

                        if (!is_wp_error($new_post_id)) {
                            $this->cache->set($cache_key, $new_post_id);
                            update_post_meta($new_post_id, 'youtube_id', $video->youtube_id);
                            $this->add_attachment($new_post_id, $video->youtube_id, $video->image);
                        }
                    }
                }
            }
            catch (YoutubeClientError $e) {
                error_log($e->getMessage());
            }
        }

    }

    public function clear_cache($postid) {

        if ($youtube_id = get_post_meta($postid, 'youtube_id', true)) {
            $cache_key = sprintf('post-%s', $youtube_id);
            $this->cache->delete($cache_key);
        }

    }

    private function add_attachment($postid, $youtube_id, $image) {

        $image = stripslashes($image);
        $uploads = wp_upload_dir();
        $image_filename = basename($image);
        $ext = pathinfo($image_filename, PATHINFO_EXTENSION);
        $filename = $youtube_id . "." . $ext;
        $fullpath = path_join($uploads['path'], $filename);

        $wp_filetype = wp_check_filetype($image_filename, null);
        if (!substr_count($wp_filetype['type'], "image")) {
            error_log(sprintf("'%s' is not a valid image", $image_filename));
            return false;
        }

        if (!$this->image_upload->save($image, $fullpath)) {
            return false;
        }

        $attachment = array(
             'post_mime_type' => $wp_filetype['type'],
             'post_title' => $youtube_id,
             'post_content' => '',
             'post_status' => 'inherit',
             'guid' => $uploads['url'] . "/" . $filename
        );
        $attachment_id = wp_insert_attachment($attachment, $fullpath, $postid);
        if (!$attachment_id) {
            error_log("Failed to save attachment");
        }

        require_once(ABSPATH . "wp-admin" . '/includes/image.php');

        $attachment_data = wp_generate_attachment_metadata(
            $attachment_id, $fullpath
        );
        wp_update_attachment_metadata($attachment_id,  $attachment_data);

        // set as featured image
        return set_post_thumbnail($postid, $attachment_id);

    }

}

class SearchItemPosts {

    public static function register() {

        $youtube_search = YoutubeSearch::create();
        $image_upload = ImageUploadFactory::create();
        $posts_handler = new SearchItemPostsHandler(
            $youtube_search, $image_upload
        );

    }

}
