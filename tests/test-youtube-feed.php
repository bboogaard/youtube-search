<?php

use WP\WPTransient;
use YoutubeSearch\YoutubeClientError;
use YoutubeSearch\YoutubeFeedHandler;
use YoutubeSearch\YoutubeFeedSearchResultParser;

/**
 * Class TestYoutubeFeedSearchResultParser
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeFeedSearchResultParser class
 */
class TestYoutubeFeedSearchResultParser extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->result_parser = new YoutubeFeedSearchResultParser();

    }

    public function test_parse_response() {

        $actual = $this->result_parser->parse_response(array(
            'items' => array(
                array(
                    'snippet' => array(
                        'title' => 'Lorem',
                        'description' => 'Lorem ipsum',
                        'publishedAt' => '2020-07-30T12:00:00Z',
                        'thumbnails' => array(
                            'default' => array(
                                'url' => '/path/to/image.jpg'
                            ),
                            'high' => array(
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

        $dt = DateTime::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            '2020-07-30T12:00:00Z',
            new DateTimeZone('UTC')
        );
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'description' => 'Lorem ipsum<br/><br/>' .
                    '<a href="https://www.youtube.com/watch?v=asdf" ' .
                    'title="https://www.youtube.com/watch?v=asdf" target="_blank">' .
                    '<img src="/path/to/image.jpg" alt="Lorem" title="Lorem" />' .
                    '</a>',
                    'publishedAt' => $dt,
                    'youtube_id' => 'asdf',
                    'url' => 'https://www.youtube.com/watch?v=asdf',
                    'thumbnail' => '/path/to/image.jpg'
                )
            )
        );
        $this->assertEquals($expected, $actual);

    }

}

/**
 * Class TestYoutubeFeedHandler
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeFeedHandler class
 */
class TestYoutubeFeedHandler extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_search = Mockery::mock('YoutubeSearch\YoutubeSearchHandler');
        $this->wp_transient = Mockery::mock('WP\WPTransient');
        $this->http = Mockery::mock('YoutubeSearch\Lib\Http');

        $this->feed_handler = new YoutubeFeedHandler(
            $this->youtube_search, $this->wp_transient, $this->http
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

        delete_option('youtube-search-feed-checksum');
        delete_option('youtube-search-feed-content');

    }

    public function test_render_feed() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);
        $this->http->shouldReceive('send_header')->times(1);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertOutputContains('<title>Youtube Search</title>', $output);
        $this->assertOutputContains(
            '<link>http://example.org/feed/youtube-search</link>', $output
        );
        $this->assertOutputContains(
            '<atom:link href="http://example.org/feed/youtube-search" rel="self" type="application/rss+xml"></atom:link>',
            $output
        );
        $this->assertOutputContains('<title>Lorem</title>', $output);
        $this->assertOutputContains('<link>/path/to/movie</link>', $output);
        $this->assertOutputContains(
            '<guid isPermaLink="true">/path/to/movie</guid>', $output
        );

        $actual = get_option('youtube-search-feed-checksum');
        $this->assertNotNull($actual);

        $actual = get_option('youtube-search-feed-content');
        $this->assertEquals($output, $actual);

    }

    public function test_render_feed_blocks_cached() {

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
        $this->http->shouldReceive('send_header')->times(1);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertOutputContains('<title>Youtube Search</title>', $output);
        $this->assertOutputContains(
            '<link>http://example.org/feed/youtube-search</link>', $output
        );
        $this->assertOutputContains(
            '<atom:link href="http://example.org/feed/youtube-search" rel="self" type="application/rss+xml"></atom:link>',
            $output
        );
        $this->assertOutputContains('<title>Lorem</title>', $output);
        $this->assertOutputContains('<link>/path/to/movie</link>', $output);
        $this->assertOutputContains(
            '<guid isPermaLink="true">/path/to/movie</guid>', $output
        );

        $actual = get_option('youtube-search-feed-checksum');
        $this->assertNotNull($actual);

        $actual = get_option('youtube-search-feed-content');
        $this->assertEquals($output, $actual);

    }

    public function test_render_feed_not_changed() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);
        $this->http->shouldReceive('send_header')->times(1);

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
        $feed_content = 'Foobar';
        $items = array(
            array(
                'title' => 'Lorem',
                'link' => '/path/to/movie',
                'description' => 'Lorem ipsum',
                'date' => new DateTime('2020-07-03'),
                'url' => '/path/to/movie'
            )
        );
        $checksum = md5(serialize($items));
        update_option('youtube-search-feed-checksum', $checksum);
        update_option('youtube-search-feed-content', $feed_content);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertEquals("Foobar", $output);

    }

    public function test_render_feed_changed() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);
        $this->http->shouldReceive('send_header')->times(1);

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
        $feed_content = 'Foobar';
        $items = array(
            array(
                'title' => 'Lorem',
                'link' => '/path/to/other-movie',
                'description' => 'Lorem ipsum',
                'date' => new DateTime('2020-07-03'),
                'url' => '/path/to/other-movie'
            )
        );
        $checksum = md5(serialize($items));
        update_option('youtube-search-feed-checksum', $checksum);
        update_option('youtube-search-feed-content', $feed_content);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertNotEquals("Foobar", $output);

    }

    public function test_render_feed_not_changed_no_content() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);
        $this->http->shouldReceive('send_header')->times(1);

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
        $feed_content = 'Foobar';
        $items = array(
            array(
                'title' => 'Lorem',
                'link' => '/path/to/movie',
                'description' => 'Lorem ipsum',
                'date' => new DateTime('2020-07-03'),
                'url' => '/path/to/movie'
            )
        );
        $checksum = md5(serialize($items));
        update_option('youtube-search-feed-checksum', $checksum);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertNotEquals("Foobar", $output);

    }

    public function test_render_feed_with_error() {

        $this->wp_transient->shouldReceive('get')
                           ->with('youtube-search-blocks')
                           ->andReturn(false);
        $this->wp_transient->shouldReceive('set')->times(1);
        $this->http->shouldReceive('send_header')->times(1);

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

        ob_start();
        $this->feed_handler->render_feed();
        $output = ob_get_clean();

        $this->assertNotEquals('', $output);

    }

}
