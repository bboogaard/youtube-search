<?php

/*
 * File name: template.loader.php
 *
 * Load templates in correct order.
 *
 */

namespace YoutubeSearch;

class TemplateLoader {

    private $locations;

    public function __construct($locations) {

        $this->locations = $locations;

    }

    public function get_template($template_name) {

        foreach ($this->locations as $location) {
            $template = path_join($location, $template_name);
            if (file_exists($template)) {
                return $template;
            }
        }

        return false;

    }

    public function render($template_name, $context, $echo=true) {

        if (!$template = $this->get_template($template_name)) {
            if (!$echo) {
                return '';
            }
            return;
        }

        if (!$echo) {
            ob_start();
        }

        foreach ($context as $key => $val) {
            $$key = $val;
        }

        include($template);

        if (!$echo) {
            return ob_get_clean();
        }

    }

}

class TemplateLoaderFactory {

    public static function create() {

        return new TemplateLoader(array(
            path_join(get_stylesheet_directory(), 'youtube-search'),
            path_join(get_template_directory(), 'youtube-search'),
            YOUTUBE_SEARCH_TEMPLATE_PATH
        ));

    }

}
