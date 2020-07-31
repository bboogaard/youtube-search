<?php

namespace YoutubeSearch;

use \Exception;
use \Google_Client;
use \Google_Exception;
use \Google_ServiceException;
use \Google_Service_YouTube;

class YoutubeService {

    private $client;

    public function __construct() {

        $api_key = get_option('youtube_search_options', array(
            'api_key' => ''
        ))['api_key'];
        $this->client = $this->create_client($api_key);

    }

    public function search($part, $params) {

        $this->throttle();

        return $this->client->search->listSearch($part, $params);

    }

    public function list($part, $params) {

        $this->throttle();

        return $this->client->videos->listVideos($part, $params);

    }

    private function create_client($api_key) {

        $client = new Google_Client();
        $client->setDeveloperKey($api_key);

        return new Google_Service_YouTube($client);

    }

    private function throttle() {

        $last_request = get_option('youtube_search_last_request', 0);
        $current_time = time();
        if ($current_time - $last_request < 1) {
            sleep(1);
        }
        update_option('youtube_search_last_request', $current_time);

    }

}

class YoutubeResultParser {

    public function parse_response($response) {

        $result = array();

        foreach ($response['items'] as $item) {
            array_push(
                $result,
                (object)array(
                    'youtube_id' => $item['id']['videoId']
                )
            );
        }

        return (object)array(
            'data' => $result
        );

    }

    protected function maybe_get_from_part($item, $part, $key) {

        if (!isset($item[$part])) {
            return null;
        }

        if (!isset($item[$part][$key])) {
            return null;
        }

        return $item[$part][$key];

    }

}

class YoutubeClientError extends Exception {

}

class YoutubeClient {

    private $youtube_service;

    private $allowed_methods = array('search', 'list');

    public function __construct(YoutubeService $youtube_service) {

        $this->youtube_service = $youtube_service;

    }

    public function __call($name, $arguments) {

        if (!in_array($name, $this->allowed_methods)) {
            throw new YoutubeClientError(sprintf("YoutubeClient has no method %s", $name));
        }

        $part = isset($arguments[0]) ? $arguments[0] : 'id';
        $params = isset($arguments[1]) && is_array($arguments[1]) ? $arguments[1] : array();
        $parser_class = isset($arguments[2]) && $arguments[2] ? $arguments[2] : YoutubeResultParser::class;
        $parser = new $parser_class();

        try {
            $response = call_user_func_array(
                array($this->youtube_service, $name), array($part, $params)
            );
            return $parser->parse_response($response);
        }
        catch (Google_ServiceException $e) {
            throw new YoutubeClientError(sprintf("Error calling youtube api: %s", $e->getMessage()));
        }
        catch (Google_Exception $e) {
            throw new YoutubeClientError(sprintf("Error calling youtube api: %s", $e->getMessage()));
        }

    }

}

class YoutubeClientFactory {

    public static function create() {

        $youtube_client = new YoutubeClient(new YoutubeService());
        return $youtube_client;

    }

}
