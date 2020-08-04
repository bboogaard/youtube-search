<?php

namespace YoutubeSearch;

class YoutubeSearchBlockHandler {

    private $template_loader, $youtube_search;

    public function __construct(YoutubeSearchHandler $youtube_search,
                                TemplateLoader $template_loader) {

        $this->youtube_search = $youtube_search;
        $this->template_loader = $template_loader;

        add_action('init', array($this, 'register'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_filter('block_categories', array($this, 'register_block_category'), 10, 2);

    }

    public function register() {

        register_block_type( 'youtube-search/search', array(
            'style' => 'youtube-search',
            'editor_script' => 'youtube-search',
            'render_callback' => array($this, 'render_block')
        ) );

    }

    public function enqueue_block_assets() {

        wp_enqueue_script(
            'youtube-search',
            plugins_url( YOUTUBE_SEARCH_BLOCKFILE, YOUTUBE_SEARCH_PATH ),
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'underscore' ),
            filemtime( plugin_dir_path( YOUTUBE_SEARCH_PATH ) . YOUTUBE_SEARCH_BLOCKFILE )
        );

        wp_enqueue_style(
            'youtube-search',
            plugins_url( 'css/youtube-search.css', YOUTUBE_SEARCH_PATH ),
            array( ),
            filemtime( plugin_dir_path( YOUTUBE_SEARCH_PATH ) . 'css/youtube-search.css' )
        );

    }

    public function register_block_category( $categories, $post ) {

        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'youtube-search',
                    'title' => __( 'Youtube Search', 'youtube-search' ),
                ),
            )
        );

    }

    public function render_block($block_attributes, $content) {

        $attributes = youtube_search_parse_attributes($block_attributes);
        $data = youtube_search_build_query($attributes);
        $data['pageToken'] = isset($_GET['pageToken']) ? $_GET['pageToken'] : '';

        try {
            $result = $this->youtube_search->search($data);
            $error = null;
        }
        catch (YoutubeClientError $e) {
            $result = null;
            $error = $e->getMessage();
        }

        return $this->template_loader->render(
            'videos.php',
            array(
                'result' => $result,
                'error' => $error,
                'showPublishedAt' => $attributes['showPublishedAt'],
                'showDuration' => $attributes['showDuration'],
                'showDefinition' => $attributes['showDefinition'],
                'showViewCount' => $attributes['showViewCount'],
                'usePaging' => $attributes['usePaging']
            ),
            false
        );

    }

}

class YoutubeSearchBlock {

    public static function register() {

        $youtube_search = YoutubeSearch::create();
        $template_loader = TemplateLoaderFactory::create();
        $search_handler = new YoutubeSearchBlockHandler(
            $youtube_search, $template_loader
        );

    }

}
