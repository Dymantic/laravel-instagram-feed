<?php

namespace Dymantic\InstagramFeed\Tests\Instagram;

use Dymantic\InstagramFeed\InstagramMedia;
use Dymantic\InstagramFeed\MediaParser;
use Dymantic\InstagramFeed\Tests\TestCase;

class MediaParserTest extends TestCase
{
    /**
     *@test
     */
    public function it_parses_an_image_correctly()
    {
        $json = '{
            "id": "18046738186210442",
      "media_type": "IMAGE",
      "media_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974",
      "caption": "test caption two",
      "permalink": "https://www.instagram.com/p/Ab12CDeFgHi/"
    }';

        $media = MediaParser::parseItem(json_decode($json, true));

        $this->assertInstanceOf(InstagramMedia::class, $media);

        $this->assertTrue($media->isImage());
        $this->assertSame(InstagramMedia::TYPE_IMAGE, $media->type);
        $this->assertSame("https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974", $media->url);
        $this->assertSame("18046738186210442", $media->id);
        $this->assertSame("test caption two", $media->caption);
        $this->assertSame("", $media->thumbnail_url);
        $this->assertSame("https://www.instagram.com/p/Ab12CDeFgHi/", $media->permalink);
        $this->assertSame("", $media->timestamp);
        $this->assertCount(0, $media->children);

    }

    /**
     *@test
     */
    public function it_parses_a_video_correctly()
    {
        $json = '{
      "id": "18033634498224799",
      "media_type": "VIDEO",
      "media_url": "https://video.xx.fbcdn.net/v/t50.2886-16/79391351_481629065798947_3744187809422239413_n.mp4?_nc_cat=102&vs=18083652679083225_3607239704&_nc_vs=HBkcFQAYJEdIZHF1d1FqWldFQkNyWUJBTFhtOWFENUJ2WXpia1lMQUFBRhUAACgAGAAbAYgHdXNlX29pbAExFQAAGAAWsrTUvY%2B%2Fn0AVAigCQzMsF0Ay90vGp%2B%2BeGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHXqBwA%3D&_nc_sid=59939d&efg=eyJ2ZW5jb2RlX3RhZyI6InZ0c192b2RfdXJsZ2VuLjcyMGZlZWQifQ%3D%3D&_nc_ohc=fO8GgEnZ468AX_Fk0Ib&_nc_ht=video.xx&oh=1731b5b44ac7a430e1f90596c15806c3&oe=5E916311&_nc_rid=d477a9015c",
      "caption": "test caption four",
      "thumbnail_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/79129220_127781772008163_6289896098224492554_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=ViJh35MyvBwAX-j7zq5&_nc_ht=scontent.xx&oh=759ed307d3f575f6cd59ea2ce59529bd&oe=5E91EFA1",
      "permalink": "https://www.instagram.com/p/Ab12CDeFgHi/"
    }';

        $media = MediaParser::parseItem(json_decode($json, true));

        $this->assertInstanceOf(InstagramMedia::class, $media);

        $this->assertTrue($media->isVideo());
        $this->assertFalse($media->isImage());
        $this->assertSame(InstagramMedia::TYPE_VIDEO, $media->type);
        $this->assertSame("https://video.xx.fbcdn.net/v/t50.2886-16/79391351_481629065798947_3744187809422239413_n.mp4?_nc_cat=102&vs=18083652679083225_3607239704&_nc_vs=HBkcFQAYJEdIZHF1d1FqWldFQkNyWUJBTFhtOWFENUJ2WXpia1lMQUFBRhUAACgAGAAbAYgHdXNlX29pbAExFQAAGAAWsrTUvY%2B%2Fn0AVAigCQzMsF0Ay90vGp%2B%2BeGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHXqBwA%3D&_nc_sid=59939d&efg=eyJ2ZW5jb2RlX3RhZyI6InZ0c192b2RfdXJsZ2VuLjcyMGZlZWQifQ%3D%3D&_nc_ohc=fO8GgEnZ468AX_Fk0Ib&_nc_ht=video.xx&oh=1731b5b44ac7a430e1f90596c15806c3&oe=5E916311&_nc_rid=d477a9015c", $media->url);
        $this->assertSame("18033634498224799", $media->id);
        $this->assertSame("test caption four", $media->caption);
        $this->assertSame("https://scontent.xx.fbcdn.net/v/t51.2885-15/79129220_127781772008163_6289896098224492554_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=ViJh35MyvBwAX-j7zq5&_nc_ht=scontent.xx&oh=759ed307d3f575f6cd59ea2ce59529bd&oe=5E91EFA1", $media->thumbnail_url);
        $this->assertSame("https://www.instagram.com/p/Ab12CDeFgHi/", $media->permalink);
        $this->assertSame("", $media->timestamp);
        $this->assertCount(0, $media->children);
    }

    /**
     *@test
     */
    public function it_parses_a_carousel_item_correctly()
    {
        $json = '{
      "id": "17853951361863258",
      "media_type": "CAROUSEL_ALBUM",
      "media_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F",
      "caption": "test caption one",
      "permalink": "https://www.instagram.com/p/Ab12CDeFgHi/",
      "children": {
        "data": [
          {
            "media_type": "IMAGE",
            "media_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F",
            "id": "17849438098899018"
          },
          {
            "media_type": "IMAGE",
            "media_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/84381272_1984995381635899_5263984109196147819_n.jpg?_nc_cat=109&_nc_sid=8ae9d6&_nc_ohc=_GH0NffaIucAX9WWveS&_nc_ht=scontent.xx&oh=0871f5013d7eff3336a8f90cc320a6d6&oe=5E921910",
            "id": "18132118615037332"
          },
          {
            "media_type": "IMAGE",
            "media_url": "https://scontent.xx.fbcdn.net/v/t51.2885-15/88164910_2338159439809338_6922195276317801534_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=UasVMdUTi0AAX-mb4IW&_nc_ht=scontent.xx&oh=4ab1132eac9766086fd268b9a80a6410&oe=5E930DAF",
            "id": "17894008966462830"
          }
        ]
      }
    }';

        $media = MediaParser::parseItem(json_decode($json, true));

        $this->assertInstanceOf(InstagramMedia::class, $media);

        $this->assertTrue($media->isCarousel());
        $this->assertSame(InstagramMedia::TYPE_CAROUSEL, $media->type);
        $this->assertSame("https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F", $media->url);
        $this->assertSame("17853951361863258", $media->id);
        $this->assertSame("test caption one", $media->caption);
        $this->assertSame("https://www.instagram.com/p/Ab12CDeFgHi/", $media->permalink);
        $this->assertSame("", $media->timestamp);
        $this->assertCount(3, $media->children);

        $this->assertSame([
            'type' => InstagramMedia::TYPE_IMAGE,
            'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F',
            'id' => '17849438098899018'
        ], $media->children[0]);

        $this->assertSame([
            'type' => InstagramMedia::TYPE_IMAGE,
            'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/84381272_1984995381635899_5263984109196147819_n.jpg?_nc_cat=109&_nc_sid=8ae9d6&_nc_ohc=_GH0NffaIucAX9WWveS&_nc_ht=scontent.xx&oh=0871f5013d7eff3336a8f90cc320a6d6&oe=5E921910',
            'id' => '18132118615037332'
        ], $media->children[1]);

        $this->assertSame([
            'type' => InstagramMedia::TYPE_IMAGE,
            'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88164910_2338159439809338_6922195276317801534_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=UasVMdUTi0AAX-mb4IW&_nc_ht=scontent.xx&oh=4ab1132eac9766086fd268b9a80a6410&oe=5E930DAF',
            'id' => '17894008966462830'
        ], $media->children[2]);
    }
}