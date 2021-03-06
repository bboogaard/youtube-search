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
                    ),
                    'contentDetails' => array(
                        'duration' => 'PT1M5S',
                        'definition' => 'hd'
                    ),
                    'statistics' => array(
                        'viewCount' => 1200
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
                    'content' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg',
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
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
                    'id' => 'asdf',
                    'contentDetails' => array(
                        'duration' => 'PT1M5S',
                        'definition' => 'hd'
                    ),
                    'statistics' => array(
                        'viewCount' => 1200
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
                    'content' => 'Lorem ipsum',
                    'image' => '/path/to/image.jpg',
                    'embed_html' => null,
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
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

        update_option('youtube_search_options', array(
            'insert_post_limit' => 1
        ));

        $this->posts_handler = new SearchItemPostsHandler(
            $this->youtube_search, $this->image_upload
        );

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

        delete_option('youtube-search-post-asdf');
        delete_option('youtube-search-insert_post_offset');
        delete_option('youtube_search_options');

    }

    public function test_insert_posts() {

        $post_ids = array();
        array_push($post_ids, wp_insert_post(array(
            'post_date' => '2020-01-01 12:00:00',
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->'
        )));
        array_push($post_ids, wp_insert_post(array(
            'post_date' => '2020-01-02 12:00:00',
            'post_title' => "Filmpjes",
            'post_name' => 'filmpjes',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        )));
        array_push($post_ids, wp_insert_post(array(
            'post_date' => '2020-01-03 12:00:00',
            'post_title' => "Films",
            'post_name' => 'films',
            'post_status' => 'publish',
            'post_content' => '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->'
        )));

        $result_1 = (object)array(
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $result_2 = (object)array(
            'data' => array(
                (object)array(
                    'title' => 'Ipsum',
                    'publishedAt' => new DateTime('2020-07-04'),
                    'youtube_id' => 'bazqux',
                    'url' => '/path/to/film',
                    'thumbnail' => '/path/to/thumb.jpg',
                    'content' => 'Foo bar baz qux',
                    'summary' => 'Foo bar',
                    'image' => '/path/to/img.jpg',
                    'embed_html' => '<iframe>oo</iframe>',
                    'duration' => '02:05',
                    'definition' => 'SD',
                    'view_count' => '1.500'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result_1, $result_2
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
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

        $actual = get_post_meta($post->ID, 'youtube_url', true);
        $expected = '/path/to/movie';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'embed_html', true);
        $expected = '<iframe></iframe>';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_search_post', true);
        $expected = $post_ids[0];
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'duration', true);
        $expected = '01:05';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'definition', true);
        $expected = 'HD';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'view_count', true);
        $expected = '1.200';
        $this->assertEquals($expected, $actual);

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
            'name' => 'ipsum'
        ));
        $this->assertEquals(count($posts), 1);
        $post = $posts[0];

        $actual = $post->post_title;
        $expected = 'Ipsum';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_date;
        $expected = '2020-07-04 00:00:00';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_content;
        $expected = 'Foo bar baz qux';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_excerpt;
        $expected = 'Foo bar';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_id', true);
        $expected = 'bazqux';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_url', true);
        $expected = '/path/to/film';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'embed_html', true);
        $expected = '<iframe>oo</iframe>';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_search_post', true);
        $expected = $post_ids[2];
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'duration', true);
        $expected = '02:05';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'definition', true);
        $expected = 'SD';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'view_count', true);
        $expected = '1.500';
        $this->assertEquals($expected, $actual);

    }

    public function test_insert_posts_with_categories() {

        $term = wp_insert_term(
            'Foo', 'category', array('slug' => 'foo')
        );

        $content = sprintf('<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true,"postsCategories":[{"term_id":%d,"name":"Foo"}]} /-->', $term['term_id']);

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
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

        $actual = get_post_meta($post->ID, 'youtube_url', true);
        $expected = '/path/to/movie';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'embed_html', true);
        $expected = '<iframe></iframe>';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_search_post', true);
        $expected = $post_id;
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'duration', true);
        $expected = '01:05';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'definition', true);
        $expected = 'HD';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'view_count', true);
        $expected = '1.200';
        $this->assertEquals($expected, $actual);

        $categories = wp_get_post_categories($post->ID, 'category');
        $this->assertEquals(count($categories), 1);

        $actual = $categories[0];
        $expected = $term['term_id'];
        $this->assertEquals($expected, $actual);

    }

    public function test_insert_posts_with_author() {

        $user_id = wp_insert_user(array(
            'user_nicename' => 'johndoe',
            'user_pass' => 'foo',
            'user_login' => 'johndoe'
        ));

        $content = sprintf('<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true,"postsAuthor":{"id":%d,"user_nicename":"johndoe"}} /-->', $user_id);

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
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

        $actual = get_post_meta($post->ID, 'youtube_url', true);
        $expected = '/path/to/movie';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'embed_html', true);
        $expected = '<iframe></iframe>';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_search_post', true);
        $expected = $post_id;
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'duration', true);
        $expected = '01:05';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'definition', true);
        $expected = 'HD';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'view_count', true);
        $expected = '1.200';
        $this->assertEquals($expected, $actual);

        $actual = $post->post_author;
        $expected = $user_id;
        $this->assertEquals($expected, $actual);

    }

    public function test_insert_posts_with_image() {

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
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

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
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

        $actual = get_post_meta($post->ID, 'youtube_url', true);
        $expected = '/path/to/movie';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'embed_html', true);
        $expected = '<iframe></iframe>';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'youtube_search_post', true);
        $expected = $post_id;
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'duration', true);
        $expected = '01:05';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'definition', true);
        $expected = 'HD';
        $this->assertEquals($expected, $actual);

        $actual = get_post_meta($post->ID, 'view_count', true);
        $expected = '1.200';
        $this->assertEquals($expected, $actual);

        $thumbnail_id = get_post_thumbnail_id($post);
        $attachment = wp_get_attachment_image_src($thumbnail_id);
        $actual = basename($attachment[0]);
        $expected = 'asdf.jpg';
        $this->assertEquals($expected, $actual);

    }

    public function test_insert_posts_nothing_to_do() {

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":false} /-->';

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 0);

    }

    public function test_insert_posts_with_delete() {

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
        ));

        $search_item_post_id = wp_insert_post(array(
            'post_title' => "Ipsum",
            'post_name' => 'ipsum',
            'post_status' => 'publish',
            'post_type' => 'youtube_searchitem'
        ));
        update_post_meta($search_item_post_id, 'youtube_search_post', $post_id);

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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andReturn(
            $result
        );

        $this->image_upload->shouldReceive('save')->andReturn(false);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
            'name' => 'ipsum'
        ));
        $this->assertEquals(count($posts), 0);

    }

    public function test_insert_posts_with_search_error() {

        $content = '<!-- wp:youtube-search/search {"query":"Lorem","order":"viewCount","videoDefinition":"high","videoDuration":"long","videoType":"","showDuration":true,"showDefinition":true,"showViewCount":true,"usePaging":false,"makePosts":true} /-->';

        $post_id = wp_insert_post(array(
            'post_title' => "Video's",
            'post_name' => 'videos',
            'post_status' => 'publish',
            'post_content' => $content
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
                    'embed_html' => '<iframe></iframe>',
                    'duration' => '01:05',
                    'definition' => 'HD',
                    'view_count' => '1.200'
                )
            )
        );

        $this->youtube_search->shouldReceive('search')->andThrow(
            new YoutubeClientError('Oops')
        );

        $this->image_upload->shouldReceive('save')->times(0);

        $this->posts_handler->insert_posts();

        $posts = get_posts(array(
            'post_type' => 'youtube_searchitem',
            'name' => 'lorem'
        ));
        $this->assertEquals(count($posts), 0);

    }

    public function test_clear_cache() {

        $post_id = wp_insert_post(array(
            'post_type' => 'youtube_searchitem',
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
