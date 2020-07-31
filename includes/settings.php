<?php

namespace YoutubeSearch;

class SettingsPage {

    private $options;

    public function __construct() {

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

    }

    public function add_admin_menu() {

        add_options_page(
            __('Youtube Search', 'youtube-search'),
            __('Youtube Search', 'youtube-search'),
            'manage_options',
            'youtube_search_settings',
            array($this, 'admin_page')
        );

    }

    public function admin_page() {

        $this->options = get_option('youtube_search_options', array(
            'api_key' => ''
        ));

        ?>

        <div class="wrap">
            <h1><?php echo __('Instellingen Youtube Search', 'youtube-search'); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('youtube_search_options_group');
                do_settings_sections('youtube_search_settings');
                submit_button();
            ?>
            </form>
        </div>

        <?php

    }

    public function settings_init() {

        register_setting(
            'youtube_search_options_group',
            'youtube_search_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'youtube_search_settings_auth',
            __('Authenticatie', 'youtube-search'),
            array($this, 'print_section_info_auth'),
            'youtube_search_settings'
        );

        add_settings_field(
            'api_key',
            __('API Key', 'youtube-search'),
            array($this, 'api_key_callback'),
            'youtube_search_settings',
            'youtube_search_settings_auth'
        );

    }

    public function sanitize($input) {

        $new_input = array();

        if(isset( $input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        return $new_input;
    }

    public function print_section_info_auth() {

        print __('Instellingen voor de Youtube API-authenticatie', 'youtube-search');

    }

    public function api_key_callback() {

        printf(
            '<input type="text" class="regular-text" id="api_key" name="youtube_search_options[api_key]" value="%s" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );

    }

}

class Settings {

    public static function register() {

        $settings_page = new SettingsPage();

    }

}
