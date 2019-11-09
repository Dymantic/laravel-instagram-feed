<?php


namespace Dymantic\InstagramFeed\Tests\Instagram;


use Dymantic\InstagramFeed\MediaParser;
use Dymantic\InstagramFeed\Tests\TestCase;

class MediaParserTest extends TestCase
{
    /**
     * @test
     */
    public function it_takes_first_element_of_carousel_media()
    {

        $mediaItemJson = file_get_contents("tests/media-response-carousel.json");
        $media = json_decode($mediaItemJson, true);
        $expected = [
            'type'     => 'video',
            'low'      => 'https://scontent.cdninstagram.com/v/t50.2886-16/76900458_178478256623168_4076056416211804672_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC6F62E&oh=b210caefa6bdc34a475eaaf8738cd0c4',
            'thumb'    => "https://scontent.cdninstagram.com/v/t50.2886-16/76900458_178478256623168_4076056416211804672_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC6F62E&oh=b210caefa6bdc34a475eaaf8738cd0c4",
            'standard' => 'https://scontent.cdninstagram.com/v/t50.2886-16/76654458_712142252530201_2497653154168507384_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC76342&oh=c9c3f361dad6a850db4d9429684c5132',
            'likes'    => 24,
        ];

        $this->assertEquals($expected, MediaParser::parseItem($media));
    }

    /**
     *@test
     */
    public function it_uses_first_image_from_carousel_if_video_ignored()
    {
        $mediaItemJson = file_get_contents("tests/media-response-carousel.json");
        $media = json_decode($mediaItemJson, true);
        $expected = [
            'type'     => 'image',
            'low'      => 'https://scontent.cdninstagram.com/vp/1ab482eb3bc515333e401b409949d489/5E3F7295/t51.2885-15/e35/s320x320/75430315_1216451901872620_1927164077805793257_n.jpg?_nc_ht=scontent.cdninstagram.com',
            'thumb'    => "https://scontent.cdninstagram.com/vp/936a41fe2b6f1cc57d496675d735f728/5E40A5ED/t51.2885-15/e35/s150x150/75430315_1216451901872620_1927164077805793257_n.jpg?_nc_ht=scontent.cdninstagram.com",
            'standard' => 'https://scontent.cdninstagram.com/vp/57caee18bbbf4d0c3c07c2d8aeea9db4/5E5587D2/t51.2885-15/e35/75430315_1216451901872620_1927164077805793257_n.jpg?_nc_ht=scontent.cdninstagram.com',
            'likes'    => 24,
        ];

        $this->assertEquals($expected, MediaParser::parseItem($media, true));
    }

    /**
     *@test
     */
    public function carousel_no_images_ignored_if_video_ignored()
    {
        $mediaItemJson = file_get_contents("tests/media-response-carousel-video-only.json");
        $media = json_decode($mediaItemJson, true);

        $this->assertNull(MediaParser::parseItem($media, true));
    }

    /**
     *@test
     */
    public function it_parses_video_correctly_if_not_ignored()
    {
        $mediaItemJson = file_get_contents("tests/media-response-video.json");
        $media = json_decode($mediaItemJson, true);
        $expected = [
            'type'     => 'video',
            'low'      => 'https://scontent.cdninstagram.com/v/t50.2886-16/67381463_462650827621229_2076526612468880047_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC776A2&oh=8873426e8dfc46896d0e336e66e54e27',
            'thumb'    => "https://scontent.cdninstagram.com/v/t50.2886-16/67381463_462650827621229_2076526612468880047_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC776A2&oh=8873426e8dfc46896d0e336e66e54e27",
            'standard' => 'https://scontent.cdninstagram.com/v/t50.2886-16/67649901_2505224839799369_3771899203538032458_n.mp4?_nc_ht=scontent.cdninstagram.com&oe=5DC76CE6&oh=3a449402ab6a28ffd42ec7add8a1e6fa',
            'likes'    => 34,
        ];

        $this->assertEquals($expected, MediaParser::parseItem($media, false));
    }

    /**
     *@test
     */
    public function ignored_video_parsed_as_null_item()
    {
        $mediaItemJson = file_get_contents("tests/media-response-video.json");
        $media = json_decode($mediaItemJson, true);

        $this->assertNull(MediaParser::parseItem($media, true));
    }

    /**
     *@test
     */
    public function images_are_parsed_as_expected()
    {
        $mediaItemJson = file_get_contents("tests/media-response-image.json");
        $media = json_decode($mediaItemJson, true);
        $expected = [
            'type'     => 'image',
            'low'      => 'https://scontent.cdninstagram.com/vp/16ff49411bd0cb3e5b1ce8c9e454ecea/5E452276/t51.2885-15/e35/s320x320/72341597_264657854451519_1511461411313543426_n.jpg?_nc_ht=scontent.cdninstagram.com',
            'thumb'    => "https://scontent.cdninstagram.com/vp/bcd36de1546b7e5b4e733d0e9b266026/5E44A886/t51.2885-15/e35/s150x150/72341597_264657854451519_1511461411313543426_n.jpg?_nc_ht=scontent.cdninstagram.com",
            'standard' => 'https://scontent.cdninstagram.com/vp/b6293858298394c4ad4c116a46fba3f6/5E46EE21/t51.2885-15/sh0.08/e35/s640x640/72341597_264657854451519_1511461411313543426_n.jpg?_nc_ht=scontent.cdninstagram.com',
            'likes'    => 20,
        ];

        $this->assertEquals($expected, MediaParser::parseItem($media, false));
    }

}