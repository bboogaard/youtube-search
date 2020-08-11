<?php

namespace YoutubeSearch;

class ShortcodeHandler {

    private $content, $template_loader;

    public function __construct(TemplateLoader $template_loader,
                                $args, $content='') {

        $this->template_loader = $template_loader;
        $this->args = $args;
        $this->content = $content;

    }

    public function render() {

        if (!$this->args['post_id']) {
            return '';
        }

        $post = get_post($this->args['post_id']);
        if (!$post || $post->post_type != 'youtube_searchitem') {
            return '';
        }

        $youtube_url = get_post_meta($post->ID, 'youtube_url', true);
        $duration = get_post_meta($post->ID, 'duration', true);
        $definition = get_post_meta($post->ID, 'definition', true);
        $view_count = get_post_meta($post->ID, 'view_count', true);
        $embed_html = get_post_meta($post->ID, 'embed_html', true);

        return $this->template_loader->render(
            'video.php',
            array(
                'content' => $this->content,
                'post_thumbnail' => get_the_post_thumbnail(
                    $post->ID,
                    $this->args['post_thumbnail_size'],
                    $this->args['post_thumbnail_class'] ?
                    array(
                        'class' => $this->args['post_thumbnail_class']
                    ) : ''
                ),
                'youtube_url' => $youtube_url,
                'duration' => $duration,
                'definition' => $definition,
                'view_count' => $view_count,
                'embed_html' => $embed_html
            ),
            false
        );

    }

}

class Shortcodes {

    public static function register() {

        add_shortcode('youtube_search_video_details', array(__CLASS__, 'render'));

    }

    public static function render($atts, $content='') {

        $args = shortcode_atts(array(
            'post_id' => null,
            'post_thumbnail_size' => 'post-thumbnail',
            'post_thumbnail_class' => ''
        ), $atts);

        $template_loader = TemplateLoaderFactory::create();

        $handler = new ShortcodeHandler($template_loader, $args, $content);
        return $handler->render();

    }

}
