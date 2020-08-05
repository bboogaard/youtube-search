<?php

namespace YoutubeSearch;

use \DateTime;
use \DateTimeZone;
use WP\WPTransient;
use YoutubeSearch\Lib\Cache;
use YoutubeSearch\Lib\Http;

class YoutubeFeedSearchResultParser extends YoutubeResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {
            $dt = DateTime::createFromFormat(
                'Y-m-d\TH:i:s\Z',
                $item['snippet']['publishedAt'],
                new DateTimeZone('UTC')
            );

            $title = $item['snippet']['title'];
            $description = $item['snippet']['description'];
            $url = sprintf('https://www.youtube.com/watch?v=%s', $item['id']['videoId']);
            $image = $item['snippet']['thumbnails']['high']['url'];

            $description = sprintf(
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

            array_push(
                $result,
                (object)array(
                    'title' => $title,
                    'description' => $description,
                    'publishedAt' => $dt,
                    'youtube_id' => $item['id']['videoId'],
                    'url' => $url,
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url']
                )
            );
        }

        return (object)array(
            'data' => $result
        );

    }

}

class YoutubeFeedHandler {

    private $cache, $feed_generator, $http, $wp_transient, $wpdb, $youtube_search;

    public function __construct(YoutubeSearchHandler $youtube_search,
                                WPTransient $wp_transient,
                                Http $http) {

        global $wpdb;

        $this->youtube_search = $youtube_search;
        $this->wp_transient = $wp_transient;
        $this->http = $http;

        $this->feed_generator = new FeedGenerator(
            'Youtube Search',
            site_url('feed/youtube-search'),
            "Cool youtube video's",
            null,
            null,
            site_url('feed/youtube-search')
        );
        $this->wpdb = $wpdb;

        $this->cache = new Cache('youtube-search-');

        add_action('init', array($this, 'add_feed'));

    }

    public function add_feed() {

        add_feed('youtube-search', array($this, 'render_feed'));

    }

    public function render_feed() {

        $items = $this->get_feed_items();
        $checksum = md5(serialize($items));
        $saved_checksum = $this->cache->get('feed-checksum', '');
        $feed_content = $this->cache->get('feed-content');
        if ($checksum == $saved_checksum && $feed_content) {
            $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
            get_option('blog_charset'));
            echo $feed_content;
            return;
        }

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        $feed_content = $this->feed_generator->generate($dt, $items);
        $this->cache->set('feed-checksum', $checksum);
        $this->cache->set('feed-content', $feed_content);
        $this->http->send_header('Content-Type: '.feed_content_type('rss-http').'; charset='.
        get_option('blog_charset'));
        echo $feed_content;

    }

    private function get_feed_items() {

        $blocks = $this->get_youtube_search_blocks();
        $items = array();
        foreach ($blocks as $block) {
            $attributes = youtube_search_parse_attributes($block['attrs']);
            $data = youtube_search_build_query($attributes);

            try {
                $result = $this->youtube_search->search(
                    $data, new YoutubeFeedSearchResultParser()
                );
                if (!empty($result->data)) {
                    foreach ($result->data as $video) {
                        array_push(
                            $items,
                            array(
                                'title' => $video->title,
                                'link' => $video->url,
                                'description' => $video->description,
                                'date' => $video->publishedAt,
                                'url' => $video->url
                            )
                        );
                    }
                }
            }
            catch (YoutubeClientError $e) {
                error_log($e->getMessage());
            }
        }
        usort(
            $items,
            function($a, $b) {
                return $a['date'] > $b['date'] ? -1 : 1;
            }
        );
        return $items;

    }

    private function get_youtube_search_blocks() {

        $blocks = $this->wp_transient->get('youtube-search-blocks');
        if ($blocks) {
            return $blocks;
        }

        $posts = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT post_content FROM " . $this->wpdb->posts . " " .
                "WHERE post_type = 'post' AND post_status = 'publish' AND " .
                "post_content LIKE %s",
                '%' . $this->wpdb->esc_like('youtube-search/search') . '%'
            )
        );
        $blocks = array();
        foreach ($posts as $post) {
            $blocks = array_merge($blocks, youtube_search_get_blocks($post));
        }
        $this->wp_transient->set('youtube-search-blocks', $blocks, DAY_IN_SECONDS);
        return $blocks;

    }

}

class YoutubeFeed {

    public static function register() {

        $youtube_search = YoutubeSearch::create();
        $wp_transient = new WPTransient();
        $http = new Http();
        $feed_handler = new YoutubeFeedHandler(
            $youtube_search, $wp_transient, $http
        );

    }

}
