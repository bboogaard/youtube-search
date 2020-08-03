<?php

/**
 * Class YoutubeSearchTestCase
 *
 */

/**
 * Base test class for the plugin with some extra helpers
 */
class YoutubeSearchTestCase extends WP_UnitTestCase {

    public function assertOutputContains($value, $output) {

        $this->assertTrue(false !== strpos($output, $value));

    }

}
