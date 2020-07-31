<?php

use YoutubeSearch\YoutubeClientError;
use YoutubeSearch\YoutubeListResultParser;
use YoutubeSearch\YoutubeSearchHandler;
use YoutubeSearch\YoutubeSearchResultParser;

/**
 * Class TestYoutubeSearchResultParser
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeSearchResultParser class
 */
class TestYoutubeSearchResultParser extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->result_parser = new YoutubeSearchResultParser();

    }

    public function test_parse_response() {

        $actual = $this->result_parser->parse_response(array(
            'items' => array(
                array(
                    'snippet' => array(
                        'title' => 'Lorem',
                        'publishedAt' => '2020-07-30T12:00:00Z',
                        'thumbnails' => array(
                            'default' => array(
                                'url' => '/path/to/image.jpg'
                            )
                        )
                    ),
                    'id' => array(
                        'videoId' => 'asdf'
                    )
                )
            ),
            'nextPageToken' => '',
            'prevPageToken' => ''
        ));
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => '30-07-2020',
                    'youtube_id' => 'asdf',
                    'url' => 'https://www.youtube.com/watch?v=asdf',
                    'thumbnail' => '/path/to/image.jpg'
                )
            ),
            'next_page' => '',
            'prev_page' => ''
        );
        $this->assertEquals($expected, $actual);

    }

}

/**
 * Tests for the YoutubeListResultParser class
 */
class TestYoutubeListResultParser extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->result_parser = new YoutubeListResultParser();

    }

    public function test_parse_response() {

        $actual = $this->result_parser->parse_response(array(
            'items' => array(
                array(
                    'contentDetails' => array(
                        'duration' => 'PT1M5S',
                        'definition' => 'hd'
                    ),
                    'id' => array(
                        'videoId' => 'asdf'
                    ),
                    'statistics' => array(
                        'viewCount' => 1200
                    )
                )
            )
        ));
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_parse_response_missing_part() {

        $actual = $this->result_parser->parse_response(array(
            'items' => array(
                array(
                    'contentDetails' => array(
                        'duration' => 'PT1M5S',
                        'definition' => 'hd'
                    ),
                    'id' => array(
                        'videoId' => 'asdf'
                    )
                )
            )
        ));
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => null
                )
            )
        );
        $this->assertEquals($expected, $actual);

    }

}

/**
 * Class TestYoutubeSearchHandler
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeSearchHandler class
 */
class TestYoutubeSearchHandler extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_client = Mockery::mock('YoutubeSearch\YoutubeClient');
        $this->wp_json = Mockery::mock('WP\WPJson');
        $this->wp_transient = Mockery::mock('WP\WPTransient');
        $this->search_handler = new YoutubeSearchHandler(
            $this->youtube_client, $this->wp_json, $this->wp_transient
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_search() {

        $data = array(
            'q' => 'Lorem'
        );

        $params = array(
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn(false);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andReturn($result);

        $this->wp_transient->shouldReceive('set')->with($cache_key, $result, DAY_IN_SECONDS);

        $this->search_handler->search($data);

        $this->assertTrue(true);

    }

    public function test_search_with_details() {

        $data = array(
            'q' => 'Lorem',
            'listPart' => 'id,contentDetails'
        );

        $params = array(
            'listPart' => 'id,contentDetails',
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn(false);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        unset($params['listPart']);

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andReturn($result);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'duration' => 'PT1M5S',
                    'definition' => 'HD',
                    'view_count' => 1200
                )
            )
        );

        $this->youtube_client->shouldReceive('list')
                             ->with(
                                 'id,contentDetails',
                                 array(
                                     'id' => 'asdf'
                                 ),
                                 YoutubeListResultParser::class
                             )
                             ->andReturn($result);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf',
                    'duration' => 'PT1M5S',
                    'definition' => 'HD',
                    'view_count' => 1200
                )
            )
        );

        $this->wp_transient->shouldReceive('set')->andReturnUsing(function($key, $value, $expiration) use($cache_key, $result) {
            $this->assertEquals($cache_key, $key);
            $this->assertEquals($result, $value);
            $this->assertEquals(DAY_IN_SECONDS, $expiration);
        });

        $this->search_handler->search($data);

        $this->assertTrue(true);

    }

    public function test_search_with_details_empty() {

        $data = array(
            'q' => 'Lorem',
            'listPart' => 'id,contentDetails'
        );

        $params = array(
            'listPart' => 'id,contentDetails',
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn(false);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        unset($params['listPart']);

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andReturn($result);

        $result = (object)array(
            'data' => array()
        );

        $this->youtube_client->shouldReceive('list')
                             ->with(
                                 'id,contentDetails',
                                 array(
                                     'id' => 'asdf'
                                 ),
                                 YoutubeListResultParser::class
                             )
                             ->andReturn($result);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        $this->wp_transient->shouldReceive('set')->andReturnUsing(function($key, $value, $expiration) use($cache_key, $result) {
            $this->assertEquals($cache_key, $key);
            $this->assertEquals($result, $value);
            $this->assertEquals(DAY_IN_SECONDS, $expiration);
        });

        $this->search_handler->search($data);

        $this->assertTrue(true);

    }

    public function test_search_cached() {

        $data = array(
            'q' => 'Lorem'
        );

        $params = array(
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn($result);

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andReturn($result);

        $this->wp_transient->shouldReceive('set')->times(0);

        $this->search_handler->search($data);

        $this->assertTrue(true);

    }

    public function test_search_json() {

        $_GET = array(
            'q' => 'Lorem'
        );

        $params = array(
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn(false);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andReturn($result);

        $this->wp_transient->shouldReceive('set')->with($cache_key, $result, DAY_IN_SECONDS);
        $this->wp_json->shouldReceive('send_success')->with($result);

        $this->search_handler->search_json();

        $this->assertTrue(true);

    }

    public function test_search_json_error() {

        $_GET = array(
            'q' => 'Lorem'
        );

        $params = array(
            'type' => 'video',
            'q' => 'Lorem',
            'maxResults' => 10,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoDuration' => 'any'
        );

        $cache_key = sprintf('youtube-search-%s', md5(serialize($params)));
        $this->wp_transient->shouldReceive('get')->with($cache_key)->andReturn(false);

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf'
                )
            )
        );

        $this->youtube_client->shouldReceive('search')
                             ->with(
                                 'id,snippet',
                                 $params,
                                 YoutubeSearchResultParser::class
                             )
                             ->andThrow(new YoutubeClientError('Oops'));

        $this->wp_transient->shouldReceive('set')->times(0);
        $this->wp_json->shouldReceive('send_error')->with(array(
            'message' => 'Oops'
        ));

        $this->search_handler->search_json();

        $this->assertTrue(true);

    }

}
