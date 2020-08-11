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

class PostListResultParser extends YoutubeListResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {

            $description = $item['snippet']['description'];
            $image = $item['snippet']['thumbnails']['high']['url'];
            $embed_html = $this->maybe_get_from_part(
                $item, 'player', 'embedHtml'
            );

            array_push(
                $result,
                (object)array(
                    'duration' => $this->get_duration(
                        $this->maybe_get_from_part(
                            $item, 'contentDetails', 'duration'
                        )
                    ),
                    'definition' => $this->get_definition(
                        $this->maybe_get_from_part(
                            $item, 'contentDetails', 'definition'
                        )
                    ),
                    'view_count' => $this->get_view_count(
                        $this->maybe_get_from_part(
                            $item, 'statistics', 'viewCount'
                        )
                    ),
                    'content' => $description,
                    'summary' => wp_trim_words($description),
                    'image' => $image,
                    'embed_html' => $embed_html
                )
            );

        }

        return (object)array(
            'data' => $result
        );

    }

}

class SearchItemPostsHandler {

    private $cache, $image_upload, $insert_post_limit, $wpdb, $youtube_search;

    public function __construct(YoutubeSearchHandler $youtube_search,
                                ImageUpload $image_upload) {

        global $wpdb;

        $this->youtube_search = $youtube_search;
        $this->image_upload = $image_upload;

        $this->cache = new Cache('youtube-search-');
        $this->wpdb = $wpdb;

        $this->insert_post_limit = get_option('youtube_search_options', array(
            'insert_post_limit' => 10
        ))['insert_post_limit'];

        add_action('youtube_search_insert_posts', array($this, 'insert_posts'));
        add_action('init', array($this, 'schedule_events'));
        add_action('before_delete_post', array($this, 'clear_cache'), 10, 1);

    }

    public function schedule_events() {

        if ( ! wp_get_schedule( 'youtube_search_insert_posts' ) ) {
            wp_schedule_event( time(), 'hourly', 'youtube_search_insert_posts');
        }

    }

    public function insert_posts() {

        $post_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(id) FROM " . $this->wpdb->posts . " " .
                "WHERE post_type = 'post' AND post_status = 'publish' AND " .
                "post_content LIKE %s",
                '%' . $this->wpdb->esc_like('youtube-search/search') . '%'
            )
        );
        $offset = $this->cache->get('insert_post_offset', 0);
        if ($offset >= $post_count) {
            $offset = 0;
        }

        $posts = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id FROM " . $this->wpdb->posts . " " .
                "WHERE post_type = 'post' AND post_status = 'publish' AND " .
                "post_content LIKE %s ORDER BY post_date LIMIT %d OFFSET %d",
                '%' . $this->wpdb->esc_like('youtube-search/search') . '%',
                $this->insert_post_limit,
                $offset
            )
        );
        foreach ($posts as $post) {
            $this->maybe_insert_posts(get_post($post->id));
        }

        $this->cache->set('insert_post_offset', $offset + $this->insert_post_limit);

    }

    private function maybe_insert_posts($post) {

        $blocks = youtube_search_get_blocks($post);

        foreach ($blocks as $block) {
            $attributes = youtube_search_parse_attributes($block['attrs']);
            if (!$attributes['makePosts']) {
                continue;
            }
            $data = youtube_search_build_query($attributes);
            $data['listPart'] = 'id,snippet,player,contentDetails,statistics';

            $search_item_post_ids = array();

            try {
                $result = $this->youtube_search->search(
                    $data, new PostSearchResultParser(), new PostListResultParser()
                );
                if ($result && !empty($result->data)) {
                    foreach ($result->data as $video) {
                        $cache_key = sprintf('post-%s', $video->youtube_id);
                        $search_item_post_id = $this->cache->get($cache_key);
                        if ($search_item_post_id) {
                            array_push($search_item_post_ids, $search_item_post_id);
                            continue;
                        }

                        $post_vars = array(
                            'post_type' => 'youtube_searchitem',
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

                        if (!is_wp_error($new_post_id)) {
                            $this->cache->set($cache_key, $new_post_id);
                            update_post_meta($new_post_id, 'youtube_search_post', $post->ID);
                            update_post_meta($new_post_id, 'youtube_id', $video->youtube_id);
                            update_post_meta($new_post_id, 'youtube_url', $video->url);
                            update_post_meta($new_post_id, 'duration', $video->duration);
                            update_post_meta($new_post_id, 'definition', $video->definition);
                            update_post_meta($new_post_id, 'view_count', $video->view_count);
                            update_post_meta($new_post_id, 'embed_html', $video->embed_html);
                            $this->add_attachment($new_post_id, $video->youtube_id, $video->image);
                            array_push($search_item_post_ids, $new_post_id);
                        }
                    }
                }
            }
            catch (YoutubeClientError $e) {
                error_log($e->getMessage());
            }
        }

        $search_item_posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
            'meta_query' => array(
                'key' => 'youtube_search_post',
                'value' => $post->ID
            )
        ));
        foreach ($search_item_posts as $search_item_post) {
            if (!in_array($search_item_post->ID, $search_item_post_ids)) {
                wp_delete_post($search_item_post->ID, true);
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
