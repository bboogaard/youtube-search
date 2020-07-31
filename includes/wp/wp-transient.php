<?php

namespace WP;

class WPTransient {

    public function get($transient) {

        return get_transient($transient);

    }

    public function set($transient, $value, $expiration) {

        return set_transient($transient, $value, $expiration);

    }

    public function delete($transient) {

        return delete_transient($transient);

    }

}
