<?php

use YoutubeSearch\FeedGenerator;

/**
 * Class TestFeedGenerator
 *
 * @package Youtube_Search
 */

/**
 * Tests for the FeedGenerator class
 */
class TestFeedGenerator extends YoutubeSearchTestCase {

    public function test_generate() {

        $feed_generator = new FeedGenerator('My feed', '/feed', 'This is my feed');

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_image() {

        $feed_generator = new FeedGenerator(
            'My feed', '/feed', 'This is my feed', '/path/to/image.jpg'
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <image><title>My feed</title>
        <link>This is my feed</link>
        <url>/path/to/image.jpg</url>
        </image>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_language() {

        $feed_generator = new FeedGenerator(
            'My feed', '/feed', 'This is my feed', null, 'nl-NL'
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <language>nl-NL</language>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_self_link() {

        $feed_generator = new FeedGenerator(
            'My feed', '/feed', 'This is my feed', null, null, '/feed'
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <atom:link href="/feed" rel="self" type="application/rss+xml"></atom:link>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_enclosures() {

        $feed_generator = new FeedGenerator('My feed', '/feed', 'This is my feed');

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt,
                'enclosures' => array(
                    array(
                        'url' => '/path/to/audio.mp3',
                        'size' => 11000,
                        'type' => 'audio/mp3'
                    )
                )
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        <enclosure length="11000" type="audio/mp3" url="/path/to/audio.mp3"></enclosure>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_author() {

        $feed_generator = new FeedGenerator('My feed', '/feed', 'This is my feed');

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt,
                'author' => 'admin'
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        <author>admin</author>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

    public function test_generate_with_id() {

        $feed_generator = new FeedGenerator('My feed', '/feed', 'This is my feed');

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 7, 1);
        $dt->setTime(12, 0, 0);
        $items = array(
            array(
                'title' => 'The article',
                'link' => '/article',
                'description' => 'This is an article',
                'date' => $dt,
                'url' => '/article',
            )
        );

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $dt->setDate(2020, 8, 1);
        $dt->setTime(18, 38, 38);
        $output = $feed_generator->generate($dt, $items);
        $expected = '<?xml version="1.0" encoding="utf-8" ?>
        <rss version="2.0">
        <channel>
        <title>My feed</title>
        <link>/feed</link>
        <description><![CDATA[This is my feed]]></description>
        <pubDate>Sat, 01 Aug 2020 18:38:38 +0200</pubDate>
        <generator>FeedWriter</generator>
        <item>
        <title>The article</title>
        <link>/article</link>
        <description><![CDATA[This is an article]]></description>
        <pubDate>Wed, 01 Jul 2020 10:00:00 +0000</pubDate>
        <guid isPermaLink="true">/article</guid>
        </item>
        </channel>
        </rss>';

        $lines = explode("\n", $output);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $actual = implode("\n", $lines);

        $lines = explode("\n", $expected);
        $lines = array_map(function($line) {
            return trim($line);
        }, $lines);
        $expected = implode("\n", $lines);

        $this->assertEquals($expected, $actual);

    }

}
