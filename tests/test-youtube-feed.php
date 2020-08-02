<?php

use WP\WPTransient;
use YoutubeSearch\YoutubeClientError;
use YoutubeSearch\YoutubeFeedHandler;

/**
 * Class TestYoutubeFeedHandler
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeFeedHandler class
 */
class TestYoutubeFeedHandler extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_search = Mockery::mock('YoutubeSearch\YoutubeSearchHandler');
        $this->wp_transient = Mockery::mock('WP\WPTransient');

        $this->feed_handler = new YoutubeFeedHandler(
            $this->youtube_search, $this->wp_transient
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_get_feed_items() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'description' => 'Lorem ipsum',
                    'youtube_id' => 'asdf',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false} /-->';

        wp_insert_post(array(
            'post_type' => 'post',
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
        ));

        $reflector = new ReflectionClass('YoutubeSearch\YoutubeFeedHandler');
        $method = $reflector->getMethod('get_feed_items');
        $method->setAccessible(true);
        $actual = $method->invoke($this->feed_handler);
        $expected = array(
            array(
                'title' => 'Lorem',
                'link' => '/path/to/movie',
                'description' => 'Lorem ipsum',
                'date' => new DateTime('2020-07-03'),
                'url' => '/path/to/movie'
            )
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_get_feed_items_blocks_cached() {

        $blocks = array(
            array(
                'attrs' => array(
                    'query' => 'Lorem',
                    'order' => 'viewCount',
                    'videoDefinition' => 'high',
                    'videoDuration' => 'long',
                    'videoType' => null,
                    'showDuration' => true,
                    'showDefinition' => true,
                    'showViewCount' => true,
                    'usePaging' => false
                )
            )
        );

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn($blocks);
        $this->wp_transient->shouldReceive('set')->times(0);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'description' => 'Lorem ipsum',
                    'youtube_id' => 'asdf',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $reflector = new ReflectionClass('YoutubeSearch\YoutubeFeedHandler');
        $method = $reflector->getMethod('get_feed_items');
        $method->setAccessible(true);
        $actual = $method->invoke($this->feed_handler);
        $expected = array(
            array(
                'title' => 'Lorem',
                'link' => '/path/to/movie',
                'description' => 'Lorem ipsum',
                'date' => new DateTime('2020-07-03'),
                'url' => '/path/to/movie'
            )
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_get_feed_items_with_error() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);

        $this->youtube_search->shouldReceive('search')->andThrow(
            new YoutubeClientError('Oops')
        );

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false} /-->';

        wp_insert_post(array(
            'post_type' => 'post',
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
        ));

        $reflector = new ReflectionClass('YoutubeSearch\YoutubeFeedHandler');
        $method = $reflector->getMethod('get_feed_items');
        $method->setAccessible(true);
        $actual = $method->invoke($this->feed_handler);
        $expected = array();
        $this->assertEquals($expected, $actual);

    }

}
