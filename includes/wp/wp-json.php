<?php

namespace WP;

class WPJson {

    public function send_success($data=null, $status_code=null) {

        wp_send_json_success($data, $status_code);

    }

    public function send_error($data=null, $status_code=null) {

        wp_send_json_error($data, $status_code);

    }

}
