<?php

function youtube_search_parse_args($args, $defaults, $allow_empty=false) {

    $args = array_filter($args, function($arg) use ($defaults) {

        return array_key_exists($arg, $defaults);

    }, ARRAY_FILTER_USE_KEY);

    $args = wp_parse_args($args, $defaults);

    foreach ($args as $key => $val) {
        if (!$val && !$allow_empty) {
            unset($args[$key]);
        }
    }

    return $args;

}

function youtube_search_render_video_details($video, $detailProps) {

    $video_details = array();
    if ($detailProps['showDuration'] && $video->duration) {
        array_push($video_details, $video->duration);
    }
    if ($detailProps['showDefinition'] && $video->definition) {
        array_push($video_details, $video->definition);
    }
    if ($detailProps['showViewCount'] && $video->view_count) {
        array_push($video_details, $video->view_count . ' views');
    }
    return implode(' - ', $video_details);

}

function youtube_search_build_nav_link($pageToken) {

    global $wp;

    $path = $wp->request;
    $params = $_GET;
    $params['pageToken'] = $pageToken;
    $query = http_build_query($params);

    return site_url($path) . '?' . $query;

}
