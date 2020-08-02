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

function youtube_search_parse_attributes($block_attributes) {

    return youtube_search_parse_args($block_attributes, array(
        'maxResults' => 10,
        'query' => '',
        'order' => 'relevance',
        'publishedAfter' => null,
        'safeSearch' => 'moderate',
        'videoDefinition' => null,
        'videoDuration' => 'any',
        'videoType' => null,
        'showPublishedAt' => true,
        'showDuration' => false,
        'showDefinition' => false,
        'showViewCount' => false,
        'usePaging' => false
    ), true);

}

function youtube_search_build_query($attributes) {

    $list_part = array();
    if ($attributes['showDuration'] || $attributes['showDefinition']) {
        array_push($list_part, 'contentDetails');
    }
    if ($attributes['showViewCount']) {
        array_push($list_part, 'statistics');
    }
    if (!empty($list_part)) {
        array_unshift($list_part, 'id');
    }

    $publishedAfter = $attributes['publishedAfter'] ?
                      substr($attributes['publishedAfter'], 0, 10) . 'T00:00:00Z' :
                      null;

    return array(
        'listPart' => implode(",", $list_part),
        'maxResults' => $attributes['maxResults'],
        'q' => $attributes['query'],
        'order' => $attributes['order'],
        'publishedAfter' => $publishedAfter,
        'safeSearch' => $attributes['safeSearch'],
        'videoDefinition' => $attributes['videoDefinition'],
        'videoDuration' => $attributes['videoDuration'],
        'videoType' => $attributes['videoType']
    );

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
