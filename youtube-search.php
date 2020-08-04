<?php
/**
 * Plugin Name:     Youtube Search
 * Plugin URI:      https://github.com/bboogaard/youtube-search/
 * Description:     Youtube search block
 * Author:          Bram Boogaard
 * Author URI:      https://www.wp-wikkel.nl/
 * Text Domain:     youtube-search
 * Domain Path:     /languages
 * Version:         1.1.0
 *
 * @package         Youtube Search
 */

// Your code starts here.
define('YOUTUBE_SEARCH_PATH', __FILE__);
define('YOUTUBE_SEARCH_TEMPLATE_PATH', path_join(plugin_dir_path(__FILE__), 'templates'));

require('youtube-search.defines.php');
require('vendor/autoload.php');
require('includes/feed-generator.php');
require('includes/functions.php');
require('includes/settings.php');
require('includes/template.loader.php');
require('includes/lib/cache.php');
require('includes/lib/http.php');
require('includes/wp/wp-json.php');
require('includes/wp/wp-transient.php');
require('includes/youtube-client.php');
require('includes/youtube-search.php');
require('includes/youtube-feed.php');
require('includes/blocks/youtube-search.php');


function youtube_search_run() {

    \YoutubeSearch\YoutubeSearch::register();
    \YoutubeSearch\YoutubeSearchBlock::register();
    \YoutubeSearch\YoutubeFeed::register();
    \YoutubeSearch\Settings::register();

}

youtube_search_run();
