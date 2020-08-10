<?php

use YoutubeSearch\ShortcodeHandler;
use YoutubeSearch\TemplateLoader;

class TestShortcodeHandler extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->template_loader = new TemplateLoader(array(YOUTUBE_SEARCH_TEMPLATE_PATH));

    }

    public function test_render() {

        $post_id = wp_insert_post(array(
            'post_type' => 'youtube_searchitem',
            'post_title' => 'Video',
            'post_name' => 'video',
            'post_status' => 'publish'
        ));
        update_post_meta($post_id, 'embed_html', '<iframe></iframe>');
        update_post_meta($post_id, 'duration', '01:05');
        update_post_meta($post_id, 'definition', 'HD');
        update_post_meta($post_id, 'view_count', '1.200');

        $shortcode_handler = new ShortcodeHandler(
            $this->template_loader,
            array(
                'post_id' => $post_id,
                'post_thumbnail_size' => 'post-thumbnail',
                'post_thumbnail_class' => ''
            )
        );

        $output = $shortcode_handler->render();

        $this->assertOutputContains('<iframe></iframe>', $output);
        $this->assertOutputContains('<em>01:05 - HD - 1.200 views</em>', $output);

    }

    public function test_render_no_embed_html() {

        $post_id = wp_insert_post(array(
            'post_type' => 'youtube_searchitem',
            'post_title' => 'Video',
            'post_name' => 'video',
            'post_status' => 'publish'
        ));
        update_post_meta($post_id, 'embed_html', null);
        update_post_meta($post_id, 'duration', '01:05');
        update_post_meta($post_id, 'definition', 'HD');
        update_post_meta($post_id, 'view_count', '1.200');
        $this->add_thumbnail($post_id);

        $shortcode_handler = new ShortcodeHandler(
            $this->template_loader,
            array(
                'post_id' => $post_id,
                'post_thumbnail_size' => 'post-thumbnail',
                'post_thumbnail_class' => 'my-image'
            )
        );

        $output = $shortcode_handler->render();

        $this->assertOutputContains('image.jpg', $output);
        $this->assertOutputContains('<em>01:05 - HD - 1.200 views</em>', $output);

    }

    public function test_render_wrong_posttype() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => 'Video',
            'post_name' => 'video',
            'post_status' => 'publish'
        ));
        update_post_meta($post_id, 'embed_html', '<iframe></iframe>');
        update_post_meta($post_id, 'duration', '01:05');
        update_post_meta($post_id, 'definition', 'HD');
        update_post_meta($post_id, 'view_count', '1.200');

        $shortcode_handler = new ShortcodeHandler(
            $this->template_loader,
            array(
                'post_id' => $post_id,
                'post_thumbnail_size' => 'post-thumbnail',
                'post_thumbnail_class' => ''
            )
        );

        $output = $shortcode_handler->render();
        $this->assertEquals('', $output);
        
    }

    function add_thumbnail($post_id) {

        $uploads = wp_upload_dir();
        $fullpath = path_join($uploads['path'], 'image.jpg');
        create_image(20, 20, $fullpath);

        $attachment = array(
             'post_mime_type' => 'image/jpeg',
             'post_title' => 'asdf',
             'post_content' => '',
             'post_status' => 'inherit',
             'guid' => $uploads['url'] . "/image.jpg"
        );
        $attachment_id = wp_insert_attachment($attachment, $fullpath, $post_id);

        require_once(ABSPATH . "wp-admin" . '/includes/image.php');

        $attachment_data = wp_generate_attachment_metadata(
            $attachment_id, $fullpath
        );
        wp_update_attachment_metadata($attachment_id,  $attachment_data);

        set_post_thumbnail($post_id, $attachment_id);

    }

}
