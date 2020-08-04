<?php

use YoutubeSearch\YoutubeClientError;
use YoutubeSearch\TemplateLoader;
use YoutubeSearch\YoutubeSearchBlockHandler;

/**
 * Class TestYoutubeSearchBlockHandler
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeSearchBlockHandler class
 */
class TestYoutubeSearchBlockHandler extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_search = Mockery::mock('YoutubeSearch\YoutubeSearchHandler');

        $this->block_handler = new YoutubeSearchBlockHandler(
            $this->youtube_search,
            new TemplateLoader(array(YOUTUBE_SEARCH_TEMPLATE_PATH))
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_render_block() {

        $block_attributes = array(
            'maxResults' => 10,
            'query' => 'Lorem',
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'showPublishedAt' => true,
            'showDuration' => false,
            'showDefinition' => false,
            'showViewCount' => false,
            'usePaging' => false
        );

        $params = array(
            'listPart' => '',
            'maxResults' => 10,
            'q' => 'Lorem',
            'order' => 'relevance',
            'publishedAfter' => null,
            'safeSearch' => 'moderate',
            'videoDefinition' => null,
            'videoDuration' => 'any',
            'videoType' => null,
            'pageToken' => ''
        );

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf',
                    'publishedAt' => '30-07-2020',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg'
                )
            ),
            'next_page' => '',
            'prev_page' => ''
        );

        $this->youtube_search->shouldReceive('search')->with($params)->andReturn(
            $result
        );

        $output = $this->block_handler->render_block($block_attributes, '');
        $this->assertOutputContains('<a href="/path/to/movie" target="_blank">', $output);
        $this->assertOutputContains('<img src="/path/to/thumbnail.jpg" alt="Lorem" align="top" />', $output);
        $this->assertOutputContains('Lorem', $output);
        $this->assertOutputContains('<em>Gepubliceerd: 30-07-2020</em>', $output);

    }

    public function test_render_block_with_details() {

        $block_attributes = array(
            'maxResults' => 10,
            'query' => 'Lorem',
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'showPublishedAt' => true,
            'showDuration' => true,
            'showDefinition' => true,
            'showViewCount' => true,
            'usePaging' => false
        );

        $params = array(
            'listPart' => 'id,contentDetails,statistics',
            'maxResults' => 10,
            'q' => 'Lorem',
            'order' => 'relevance',
            'publishedAfter' => null,
            'safeSearch' => 'moderate',
            'videoDefinition' => null,
            'videoDuration' => 'any',
            'videoType' => null,
            'pageToken' => ''
        );

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf',
                    'publishedAt' => '30-07-2020',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            ),
            'next_page' => '',
            'prev_page' => ''
        );

        $this->youtube_search->shouldReceive('search')->with($params)->andReturn(
            $result
        );

        $output = $this->block_handler->render_block($block_attributes, '');
        $this->assertOutputContains('01:05 - HD - 1.200 views', $output);

    }

    public function test_render_block_with_nav_link() {

        $block_attributes = array(
            'maxResults' => 10,
            'query' => 'Lorem',
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'showPublishedAt' => true,
            'showDuration' => false,
            'showDefinition' => false,
            'showViewCount' => false,
            'usePaging' => true
        );

        $params = array(
            'listPart' => '',
            'maxResults' => 10,
            'q' => 'Lorem',
            'order' => 'relevance',
            'publishedAfter' => null,
            'safeSearch' => 'moderate',
            'videoDefinition' => null,
            'videoDuration' => 'any',
            'videoType' => null,
            'pageToken' => ''
        );

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'youtube_id' => 'asdf',
                    'publishedAt' => '30-07-2020',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg'
                )
            ),
            'next_page' => 'qux',
            'prev_page' => ''
        );

        $this->youtube_search->shouldReceive('search')->with($params)->andReturn(
            $result
        );

        $output = $this->block_handler->render_block($block_attributes, '');
        $this->assertOutputContains('<a href="http://example.org?pageToken=qux">Volgende</a>', $output);

    }

    public function test_render_block_with_error() {

        $block_attributes = array(
            'maxResults' => 10,
            'query' => 'Lorem',
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'showPublishedAt' => true,
            'showDuration' => false,
            'showDefinition' => false,
            'showViewCount' => false,
            'usePaging' => false
        );

        $params = array(
            'listPart' => '',
            'maxResults' => 10,
            'q' => 'Lorem',
            'order' => 'relevance',
            'publishedAfter' => null,
            'safeSearch' => 'moderate',
            'videoDefinition' => null,
            'videoDuration' => 'any',
            'videoType' => null,
            'pageToken' => ''
        );

        $this->youtube_search->shouldReceive('search')->with($params)->andThrow(
            new YoutubeClientError('Oops')
        );

        $output = $this->block_handler->render_block($block_attributes, '');
        $this->assertOutputContains("Er is een fout opgetreden bij het laden van de video's", $output);

    }

}
