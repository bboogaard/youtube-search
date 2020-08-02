<?php

use YoutubeSearch\YoutubeClient;
use YoutubeSearch\YoutubeClientError;
use YoutubeSearch\YoutubeResultParser;

/**
 * Class TestYoutubeClient
 *
 * @package Youtube_Search
 */

/**
 * Tests for the YoutubeClient class
 */
class TestYoutubeClient extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_service = Mockery::mock('YoutubeSearch\YoutubeService');
        $this->youtube_client = new YoutubeClient($this->youtube_service);

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

	public function test_search() {

        $this->youtube_service->shouldReceive('search')
                              ->with('id', array('maxResults' => 10))
                              ->andReturn(array(
                                  'items' => array(
                                      array(
                                          'id' => array(
                                              'videoId' => 'asdf'
                                          )
                                      )
                                  )
                              ));

        $actual = $this->youtube_client->search(
            'id',
            array('maxResults' => 10),
            new YoutubeResultParser()
        );
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'youtube_id' => 'asdf'
                )
            )
        );
        $this->assertEquals($expected, $actual);

	}

    public function test_list() {

        $this->youtube_service->shouldReceive('list')
                              ->with('id', array('id' => 'asdf'))
                              ->andReturn(array(
                                  'items' => array(
                                      array(
                                          'id' => array(
                                              'videoId' => 'asdf'
                                          )
                                      )
                                  )
                              ));

        $actual = $this->youtube_client->list(
            'id',
            array('id' => 'asdf'),
            new YoutubeResultParser()
        );
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'youtube_id' => 'asdf'
                )
            )
        );
        $this->assertEquals($expected, $actual);

	}

    public function test_with_exception() {

        $this->youtube_service->shouldReceive('search')
                              ->with('id', array('maxResults' => 10))
                              ->andThrow(new Google_Exception('Oops'));

        try {
            $actual = $this->youtube_client->search(
                'id',
                array('maxResults' => 10),
                new YoutubeResultParser()
            );
            throw new Exception("Exception not raised");
        }
        catch (YoutubeClientError $e) {
            $this->assertEquals(
                'Error calling youtube api: Oops',
                $e->getMessage()
            );
        }

	}

    public function test_unallowed_method() {

        $this->youtube_service->shouldReceive('search')
                              ->times(0);

        try {
            $this->youtube_client->get(
                'id',
                array('maxResults' => 10),
                new YoutubeResultParser()
            );
            throw new Exception('Exception not raised');
        }
        catch (YoutubeClientError $e) {
            $this->assertEquals(
                'YoutubeClient has no method get',
                $e->getMessage()
            );
        }

	}

}
