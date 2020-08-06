<?php

use YoutubeSearch\Lib\ImageUpload;

/**
 * Class TestImageUpload
 *
 * @package Youtube_Search
 */

/**
 * Tests for the ImageUpload class
 */
class TestImageUpload extends YoutubeSearchTestCase {

    function setUp() {

        parent::setUp();

        $this->http = Mockery::mock('YoutubeSearch\Lib\Http');
        $this->image_upload = new ImageUpload($this->http);

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_save() {

        $image = create_image(20, 20);

        $uploads = wp_upload_dir();
        $filename = uniqid() . 'jpg';
        $fullpath = path_join($uploads['path'], $filename);

        $this->http->shouldReceive('get')->times(1)
                                         ->with('/path/to/image.jpg')
                                         ->andReturn(array(
                                             'body' => $image
                                         ));

        $actual = $this->image_upload->save('/path/to/image.jpg', $fullpath);
        $this->assertTrue($actual);

        $saved_image = file_get_contents($fullpath);
        $this->assertEquals($image, $saved_image);

    }

    public function test_save_with_error() {

        $image = create_image(20, 20);

        $uploads = wp_upload_dir();
        $filename = uniqid() . 'jpg';
        $fullpath = path_join($uploads['path'], $filename);

        $this->http->shouldReceive('get')->times(1)
                                         ->with('/path/to/image.jpg')
                                         ->andReturn(new WP_Error('', 'Oops'));

        $actual = $this->image_upload->save('/path/to/image.jpg', $fullpath);
        $this->assertFalse($actual);

    }

}
