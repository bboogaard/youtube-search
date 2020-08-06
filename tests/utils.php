<?php

function create_image($width, $height, $file, $text='Sample image') {

    // Create a blank image and add some text
    $im = imagecreatetruecolor($width, $height);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 1, 5, 5,  $text, $text_color);

    // Save the image as 'simpletext.jpg'
    imagejpeg($im, $file);

    // Free up memory
    imagedestroy($im);

}
