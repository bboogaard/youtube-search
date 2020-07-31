<?php

namespace YoutubeSearch;

use \DateTime;
use \DateTimeZone;
use WP\WPJson;
use WP\WPTransient;

class YoutubeSearchResultParser extends YoutubeResultParser {

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
                    'publishedAt' => $dt->format('d-m-Y'),
                    'youtube_id' => $item['id']['videoId'],
                    'url' => sprintf('https://www.youtube.com/watch?v=%s', $item['id']['videoId']),
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url']
                )
            );
        }

        return (object)array(
            'next_page' => $response['nextPageToken'],
            'prev_page' => $response['prevPageToken'],
            'data' => $result
        );

    }

}

class YoutubeListResultParser extends YoutubeResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {
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
                    )
                )
            );
        }

        return (object)array(
            'data' => $result
        );

    }

    private function get_duration($value) {

        if (!$value) {
            return null;
        }

        if (preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $value, $matches)) {
            $hours = $matches[1] ? intval($matches[1]) : 0;
            $minutes = $matches[2] ? intval($matches[2]) : 0;
            $seconds = $matches[3] ? intval($matches[3]) : 0;
            $hour_part = $hours ? sprintf('%02d:', $hours) : '';
            return sprintf('%s%02d:%02d', $hour_part, $minutes, $seconds);
        }

        return null;

    }

    private function get_definition($value) {

        if (!$value) {
            return null;
        }

        return strtoupper($value);

    }

    private function get_view_count($value) {

        if (!$value) {
            return null;
        }

        return number_format(intval($value), 0, "", ".");

    }

}

class YoutubeSearchHandler {

    private $wp_json, $wp_transient, $youtube_client;

    public function __construct(YoutubeClient $youtube_client,
                                WPJson $wp_json,
                                WPTransient $wp_transient) {

        $this->youtube_client = $youtube_client;
        $this->wp_json = $wp_json;
        $this->wp_transient = $wp_transient;

        add_action('wp_ajax_youtube_search', array($this, 'search_json'));

    }

    public function search_json() {

        try {
            $result = $this->search($_GET);
            $this->wp_json->send_success($result);
        }
        catch (YoutubeClientError $e) {
            $this->wp_json->send_error(array(
                'message' => $e->getMessage()
            ));
        }

    }

    public function search($data) {

        $params = youtube_search_parse_args($data, array(
            'listPart' => '',
            'type' => 'video',
            'q' => '',
            'maxResults' => 10,
            'order' => 'relevance',
            'publishedAfter' => null,
            'safeSearch' => 'moderate',
            'videoDefinition' => null,
            'videoDuration' => 'any',
            'videoType' => null,
            'pageToken' => ''
        ));

        $cache_key = $this->get_cache_key($params);
        $result = $this->wp_transient->get($cache_key);
        if ($result) {
            return $result;
        }

        if (isset($params['listPart'])) {
            $list_part = $params['listPart'];
            unset($params['listPart']);
        }
        else {
            $list_part = '';
        }

        $result = $this->youtube_client->search(
            'id,snippet', $params, YoutubeSearchResultParser::class
        );
        if ($result) {
            if ($list_part) {
                foreach ($result->data as $video) {
                    $video_result = $this->youtube_client->list(
                        $list_part,
                        array(
                            'id' => $video->youtube_id
                        ),
                        YoutubeListResultParser::class
                    );
                    if (!empty($video_result->data)) {
                        $video_details = $video_result->data[0];
                        $video->duration = $video_details->duration;
                        $video->definition = $video_details->definition;
                        $video->view_count = $video_details->view_count;
                    }
                }
            }
            $this->wp_transient->set($cache_key, $result, DAY_IN_SECONDS);
            return $result;
        }

        return null;

    }

    private function get_cache_key($params) {

        return sprintf('youtube-search-%s', md5(serialize($params)));

    }

}

class YoutubeSearch {

    public static function create() {

        $youtube_client = YoutubeClientFactory::create();
        return new YoutubeSearchHandler(
            $youtube_client,
            new WPJson(),
            new WPTransient()
        );

    }

    public static function register() {

        self::create();

    }

}
