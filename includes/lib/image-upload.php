<?php
/*
 * File name: image-upload.php
 */

namespace YoutubeSearch\Lib;

/*
 * Class name: ImageUpload
 * Purpose: save images
 */
class ImageUpload {

    private $http;

    public function __construct(Http $http) {

        $this->http = $http;

    }

    public function save($src_url, $dest_filename) {

        $image_string = $this->download($src_url);
        if (!$image_string) {
            return false;
        }

        return false !== file_put_contents($dest_filename, $image_string);

    }

    private function download($url) {

        $response = $this->http->get($url);
        if (is_wp_error($response)) {
            error_log($response->get_error_message());
            return null;
        }

        return $response['body'];

    }

}

class ImageUploadFactory {

    public static function create() {

        $http = new Http();
        return new ImageUpload($http);

    }

}
