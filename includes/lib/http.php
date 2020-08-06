<?php

namespace YoutubeSearch\Lib;

class Http {

    public function send_header($header, $replace=true, $http_response_code=0) {

        header($header, $replace, $http_response_code);

    }

    public function get($url, $args=null) {

        return wp_remote_get($url, $args);

    }

}
