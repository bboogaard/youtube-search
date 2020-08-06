<?php

use YoutubeSearch\PostListResultParser;
use YoutubeSearch\PostSearchResultParser;
use YoutubeSearch\SearchItemPostsHandler;
use YoutubeSearch\YoutubeClientError;

/**
 * Class TestPostSearchResultParser
 *
 * @package Youtube_Search
 */

/**
 * Tests for the PostSearchResultParser class
 */
class TestPostSearchResultParser extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->result_parser = new PostSearchResultParser();

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

        $dt = DateTime::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            '2020-07-30T12:00:00Z',
            new DateTimeZone('UTC')
        );
        $expected = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
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
 * Class TestPostListResultParser
 *
 * @package Youtube_Search
 */

/**
 * Tests for the PostListResultParser class
 */
class TestPostListResultParser extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->result_parser = new PostListResultParser();

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
                    'id' => 'asdf',
                    'player' => array(
                        'embedHtml' => '<iframe></iframe>'
                    )
                )
            ),
            'nextPageToken' => '',
            'prevPageToken' => ''
        ));

        $expected = (object)array(
            'data' => array(
                (object)array(
                    'summary' => 'Lorem ipsum',
                    'content' => 'Lorem ipsum<br/><br/><iframe></iframe>',
                    'image' => '/path/to/image.jpg'
                )
            )
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_parse_response_no_embed_html() {

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
                    'id' => 'asdf'
                )
            ),
            'nextPageToken' => '',
            'prevPageToken' => ''
        ));

        $expected = (object)array(
            'data' => array(
                (object)array(
                    'summary' => 'Lorem ipsum',
                    'content' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );
        $this->assertEquals($expected, $actual);

    }

}

/**
 * Tests for the SearchItemPostsHandler class
 */
class TestSearchItemPostsHandler extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->youtube_search = Mockery::mock('YoutubeSearch\YoutubeSearchHandler');
        $this->image_upload = Mockery::mock('YoutubeSearch\Lib\ImageUpload');

        $this->feed_handler = new SearchItemPostsHandler(
            $this->youtube_search, $this->image_upload
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

        delete_option('youtube-search-post-asdf');

    }

    public function test_maybe_insert_posts() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 1);
        $post = $posts[0];

        $actual = $post->post_title;
        $expected = 'Lorem';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_date;
        $expected = '2020-07-03 00:00:00';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_content;
        $expected = 'Lorem ipsum dolor sit amet';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_excerpt;
        $expected = 'Lorem ipsum';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_id', true);
        $expected = 'asdf';
        $this->assertEquals($expected, $actual);

    }

    public function test_maybe_insert_posts_with_categories() {

        $term = wp_insert_term(
            'Foo', 'category', array('slug' => 'foo')
        );

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $content = sprintf('<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true,"postsCategories":[{"term_id":%d,"name":"Foo"}]} /-->', $term['term_id']);

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 1);
        $post = $posts[0];

        $actual = $post->post_title;
        $expected = 'Lorem';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_date;
        $expected = '2020-07-03 00:00:00';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_content;
        $expected = 'Lorem ipsum dolor sit amet';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_excerpt;
        $expected = 'Lorem ipsum';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_id', true);
        $expected = 'asdf';
        $this->assertEquals($expected, $actual);

        $categories = wp_get_post_categories($post->ID, 'category');
        $this->assertEquals(count($categories), 1);

        $actual = $categories[0];
        $expected = $term['term_id'];
        $this->assertEquals($expected, $actual);

    }

    public function test_maybe_insert_posts_with_author() {

        $user_id = wp_insert_user(array(
            'user_nicename' => 'johndoe',
            'user_pass' => 'foo',
            'user_login' => 'johndoe'
        ));

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $content = sprintf('<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true,"postsAuthor":{"id":%d,"user_nicename":"johndoe"}} /-->', $user_id);

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 1);
        $post = $posts[0];

        $actual = $post->post_title;
        $expected = 'Lorem';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_date;
        $expected = '2020-07-03 00:00:00';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_content;
        $expected = 'Lorem ipsum dolor sit amet';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_excerpt;
        $expected = 'Lorem ipsum';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_id', true);
        $expected = 'asdf';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_author;
        $expected = $user_id;
        $this->assertEquals($expected, $actual);

    }

    public function test_maybe_insert_posts_with_image() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(true);

        $uploads = wp_upload_dir();
        $fullpath = path_join($uploads['path'], 'image.jpg');
        create_image(20, 20, $fullpath);

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 1);
        $post = $posts[0];

        $actual = $post->post_title;
        $expected = 'Lorem';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_date;
        $expected = '2020-07-03 00:00:00';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_content;
        $expected = 'Lorem ipsum dolor sit amet';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_excerpt;
        $expected = 'Lorem ipsum';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_id', true);
        $expected = 'asdf';
        $this->assertEquals($expected, $actual);

        $thumbnail_id = get_post_thumbnail_id($post);
        $attachment = wp_get_attachment_image_src($thumbnail_id);
        $actual = basename($attachment[0]);
        $expected = 'asdf.jpg';
        $this->assertEquals($expected, $actual);

    }

    public function test_maybe_insert_posts_nothing_to_do() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":false} /-->';

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 0);

    }

    public function test_maybe_insert_posts_with_search_error() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));

        $result = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Lorem',
                    'publishedAt' => new DateTime('2020-07-03'),
                    'youtube_id' => 'asdf',
                    'url' => '/path/to/movie',
                    'thumbnail' => '/path/to/thumbnail.jpg',
                    'content' => 'Lorem ipsum dolor sit amet',
                    'summary' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg',
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andThrow(
            new YoutubeClientError('Oops')
        );

        $this->image_upload->shouldReceive('save')->times(0);

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));

        $posts = get_posts(array(
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 0);

    }

    public function test_clear_cache() {

        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));
        update_post_meta($post_id, 'youtube_id', 'asdf');
        update_option('youtube-search-post-asdf', $post_id);

        wp_delete_post($post_id, true);

        $actual = get_option('youtube-search-post-asdf');
        $this->assertFalse($actual);

    }

}
