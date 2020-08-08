<?php

namespace YoutubeSearch;

class SearchItemPostTypeHandler {

    public function __construct() {

        add_action('init', array($this, 'register'));

    }

    public function register() {

        $labels = array(
            'name'                  => _x( 'Youtube-zoekresultaten', 'Post type general name', 'textdomain' ),
            'singular_name'         => _x( 'Youtube-zoekresultaat', 'Post type singular name', 'textdomain' ),
            'menu_name'             => _x( 'Youtube-zoekresultaten', 'Admin Menu text', 'textdomain' ),
            'name_admin_bar'        => _x( 'Youtube-zoekresultaat', 'Add New on Toolbar', 'textdomain' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => true,
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'taxonomies'         => array('category'),
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
        );

        register_post_type('youtube_searchitem', $args);

    }

}

class SearchItemPostType {

    public static function register() {

        $posttype_handler = new SearchItemPostTypeHandler();

    }

}
