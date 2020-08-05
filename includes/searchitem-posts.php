<?php

namespace YoutubeSearch;

use \DateTime;
use \DateTimeZone;
use YoutubeSearch\Lib\Cache;

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
            $summary = sprintf(
                '%s<br/><br/>' .
                '<a href="%s" title="%s" target="_blank">' .
                '<img src="%s" alt="%s" title="%s" />' .
                '</a>',
                $description,
                $url,
                $url,
                $image,
                esc_html($title),
                esc_html($title)
            );
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
                    'summary' => $summary
                )
            );

        }

        return (object)array(
            'data' => $result
        );

    }

}

class SearchItemPostsHandler {

    private $cache, $youtube_search;

    public function __construct(YoutubeSearchHandler $youtube_search) {

        $this->youtube_search = $youtube_search;

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
                if (!empty($result->data)) {
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

}

class SearchItemPosts {

    public static function register() {

        $youtube_search = YoutubeSearch::create();
        $posts_handler = new SearchItemPostsHandler($youtube_search);

    }

}