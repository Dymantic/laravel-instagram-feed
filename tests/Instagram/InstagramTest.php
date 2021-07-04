<?php

namespace Dymantic\InstagramFeed\Tests\Instagram;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Instagram;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Mail;

class InstagramTest extends TestCase
{
    use FakesInstagramCalls;

    private $instagram;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'instagram-feed' => [
                'client_id'     => 'TEST_CLIENT_ID',
                'client_secret' => 'TEST_CLIENT_SECRET',
                'auth_callback_route'  => 'instagram'
            ]
        ]);
    }

    /**
     * @test
     */
    public function it_can_provide_a_auth_url_for_a_given_profile()
    {
        $profile = Profile::create(['username' => 'test_user']);

        $full_redirect_uri = 'http://test.test/instagram';

        $expected = "https://api.instagram.com/oauth/authorize/?client_id=TEST_CLIENT_ID&redirect_uri=$full_redirect_uri&scope=user_profile,user_media&response_type=code&state={$profile->id}";

        $instagram = app(Instagram::class);

        $uri = $instagram->authUrlForProfile($profile);

        $this->assertEquals($expected, $uri);
    }

    /**
     * @test
     */
    public function profile_auth_url_uses_config_base_url_if_present()
    {
        $profile = Profile::create(['username' => 'test_user']);
        config(['instagram-feed.base_url' => 'https://test-base-url.test']);
        config(['auth_callback_route' => 'instagram']);

        $full_redirect_uri = 'https://test-base-url.test/instagram';

        $expected = "https://api.instagram.com/oauth/authorize/?client_id=TEST_CLIENT_ID&redirect_uri=$full_redirect_uri&scope=user_profile,user_media&response_type=code&state={$profile->id}";

        $instagram = app(Instagram::class);

        $uri = $instagram->authUrlForProfile($profile);

        $this->assertEquals($expected, $uri);
    }

    

    /**
     * @test
     */
    public function it_makes_a_request_for_a_token_for_a_given_profile_after_a_successful_auth_request()
    {
        $profile = Profile::create(['username' => 'test_user']);
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('post')
                   ->with($this->equalTo("https://api.instagram.com/oauth/access_token"), $this->equalTo([
                       'client_id'     => 'TEST_CLIENT_ID',
                       'client_secret' => 'TEST_CLIENT_SECRET',
                       'grant_type'    => 'authorization_code',
                       'redirect_uri'  => "http://test.test/instagram",
                       'code'          => 'TEST_REQUEST_CODE'
                   ]))
                   ->willReturn($this->validTokenDetails());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);


        $this->assertEquals(
            $this->validTokenDetails(),
            $instagram->requestTokenForProfile($profile, $this->successAuthRequest())
        );
    }

    /**
     *@test
     */
    public function it_gets_user_details_from_short_lived_token()
    {
        $profile = Profile::create(['username' => 'test_user']);
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://graph.instagram.com/FAKE_USER_ID?fields=id,username&access_token=VALID_ACCESS_TOKEN"))
                   ->willReturn($this->validUserDetails());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);


        $this->assertEquals(
            $this->validUserDetails(),
            $instagram->fetchUserDetails($this->validTokenDetails())
        );
    }

    /**
     *@test
     */
    public function it_can_exchange_a_short_lived_token_for_a_long_lived_token()
    {
        $profile = Profile::create(['username' => 'test_user']);
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=TEST_CLIENT_SECRET&access_token=VALID_ACCESS_TOKEN"))
                   ->willReturn($this->validLongLivedToken());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);


        $this->assertEquals(
            $this->validLongLivedToken(),
            $instagram->exchangeToken($this->validTokenDetails())
        );
    }

    /**
     *@test
     */
    public function it_can_refresh_a_long_lived_token()
    {
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=VALID_LONG_LIVED_TOKEN"))
                   ->willReturn($this->refreshedLongLivedToken());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);


        $this->assertEquals(
            $this->refreshedLongLivedToken(),
            $instagram->refreshToken('VALID_LONG_LIVED_TOKEN')
        );
    }

    /**
     *@test
     */
    public function it_can_fetch_media_for_a_given_access_token()
    {
        $profile = Profile::create(['username' => 'test user']);
        $token = AccessToken::createFromResponseArray($profile, $this->validUserWithToken());

        $expected_url = "https://graph.instagram.com/{$token->user_id}/media?fields=caption,id,media_type,media_url,thumbnail_url,permalink,children{media_type,media_url},timestamp&limit=88&access_token={$token->access_code}";

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("$expected_url"))
                   ->willReturn($this->exampleMediaResponse());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token, $limit = 88);

        $expected = [
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F',
                'caption' => "test caption one",
                'id' => '17853951361863258',
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
                'thumbnail_url' => '',
                'is_carousel' => true,
                'children' => [
                    [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F",
                        "id" => "17849438098899018"
                    ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/84381272_1984995381635899_5263984109196147819_n.jpg?_nc_cat=109&_nc_sid=8ae9d6&_nc_ohc=_GH0NffaIucAX9WWveS&_nc_ht=scontent.xx&oh=0871f5013d7eff3336a8f90cc320a6d6&oe=5E921910",
                        "id" => "18132118615037332"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88164910_2338159439809338_6922195276317801534_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=UasVMdUTi0AAX-mb4IW&_nc_ht=scontent.xx&oh=4ab1132eac9766086fd268b9a80a6410&oe=5E930DAF",
                        "id" => "17894008966462830"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/89117981_2510959432554564_8299423274477931900_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=6Aodzlz0DI8AX9l8fnl&_nc_ht=scontent.xx&oh=76729707ca2daba3dddc4ca15146114f&oe=5E930DF7",
                        "id" => "17879363497541148"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/82386550_2551961055084313_287099188903912380_n.jpg?_nc_cat=100&_nc_sid=8ae9d6&_nc_ohc=9ef-YjDeuaUAX9oAc-H&_nc_ht=scontent.xx&oh=437644b122ccaa78fe7dc95de72871c7&oe=5E91CD95",
                        "id" => "17852313715894892"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/87695409_507583723499465_4522591678214389436_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=RRmJ0hA-8osAX9Y8kyy&_nc_ht=scontent.xx&oh=09dfd22f9e96fd61d6630ac3de6f413d&oe=5E90BED7",
                        "id" => "17859657748756529"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88213032_2785656941528471_2783562451851138997_n.jpg?_nc_cat=105&_nc_sid=8ae9d6&_nc_ohc=5zHF0n4CH3kAX-WogX_&_nc_ht=scontent.xx&oh=8d4eb96ed7e60877a99e11c7bd8f98ff&oe=5E929B6B",
                        "id" => "18085866604160687"
                      ],
                      [
                        "type" => "video",
                        "url" => "https://video.xx.fbcdn.net/v/t50.31694-16/87980268_1553347528164878_8967566556928578472_n.mp4?_nc_cat=109&_nc_sid=8ae9d6&_nc_ohc=st4e4abCi0sAX-T37fs&_nc_ht=video.xx&oh=da7fe6fb1546189ce4ff0420af168c81&oe=5E9068D1",
                        "id" => "17858673856780100"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/89060452_204421423969595_9028329592920102436_n.jpg?_nc_cat=111&_nc_sid=8ae9d6&_nc_ohc=wNX4e6_6PkIAX96Xzta&_nc_ht=scontent.xx&oh=3efb5bbf73bd4c151b14f22a8f724f85&oe=5E9068A8",
                        "id" => "18089802349183081"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/87854876_499080161046701_3462268559168413828_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=gp5kIS3kiJ8AX_lajeg&_nc_ht=scontent.xx&oh=1c90e7f4fa5f2c82e880dbd3fab1202f&oe=5E92FF1F",
                        "id" => "17906447419421821"
                      ]
                ]
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'id' => '18046738186210442',
                'caption' => "test caption two",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
                'thumbnail_url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'is_carousel' => false,
                'children' => []
            ],
            [
                'type' => 'video',
                'url' => 'https://video.xx.fbcdn.net/v/t50.2886-16/80075364_501004160505270_3520263354313331489_n.mp4?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=fXXNJuZcyXEAX8rD8l8&_nc_ht=video.xx&oh=e1cbd15a0f23db1f7d5a5f6ddd2ace83&oe=5E9400C7',
                'id' => '18068269231170160',
                'caption' => "test caption three",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
                'thumbnail_url' => '',
                'is_carousel' => true,
                'children' => [
                    [
                        "type" => "video",
                        "url" => "https://video.xx.fbcdn.net/v/t50.2886-16/80075364_501004160505270_3520263354313331489_n.mp4?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=fXXNJuZcyXEAX8rD8l8&_nc_ht=video.xx&oh=e1cbd15a0f23db1f7d5a5f6ddd2ace83&oe=5E9400C7",
                        "id" => "17846480635805285"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/73475359_561750917995932_8049459030244731697_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=Z2GNsIN-PmQAX_41ocV&_nc_ht=scontent.xx&oh=544f90b575c9fdee92f7590d16c046e7&oe=5E91D790",
                        "id" => "18049771285205991"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/75397694_3051536841540773_7421191993117550003_n.jpg?_nc_cat=101&_nc_sid=8ae9d6&_nc_ohc=6VcQl7GL0V4AX90J_Sz&_nc_ht=scontent.xx&oh=ec8753a6bb908e6df2f9033eddf7845d&oe=5E914860",
                        "id" => "17854146172710573"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/76881970_125876788524569_9194242390680601811_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=DbLu1RYIY28AX_Y_NZS&_nc_ht=scontent.xx&oh=4a9e736b9c4d04e33255238705b3daae&oe=5E91A61C",
                        "id" => "18120976165009091"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/79806151_2512679375668563_7275185319939303133_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=qGj9efDO_yMAX9r-nFK&_nc_ht=scontent.xx&oh=d62aebba7c3a698ed9060a73a54334c7&oe=5E914E87",
                        "id" => "18107139619074168"
                      ]
                ]
            ],
            [
                'type' => 'video',
                'url' => 'https://video.xx.fbcdn.net/v/t50.2886-16/79391351_481629065798947_3744187809422239413_n.mp4?_nc_cat=102&vs=18083652679083225_3607239704&_nc_vs=HBkcFQAYJEdIZHF1d1FqWldFQkNyWUJBTFhtOWFENUJ2WXpia1lMQUFBRhUAACgAGAAbAYgHdXNlX29pbAExFQAAGAAWsrTUvY%2B%2Fn0AVAigCQzMsF0Ay90vGp%2B%2BeGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHXqBwA%3D&_nc_sid=59939d&efg=eyJ2ZW5jb2RlX3RhZyI6InZ0c192b2RfdXJsZ2VuLjcyMGZlZWQifQ%3D%3D&_nc_ohc=fO8GgEnZ468AX_Fk0Ib&_nc_ht=video.xx&oh=1731b5b44ac7a430e1f90596c15806c3&oe=5E916311&_nc_rid=d477a9015c',
                'id' => '18033634498224799',
                'caption' => "test caption four",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
                'thumbnail_url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/79129220_127781772008163_6289896098224492554_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=ViJh35MyvBwAX-j7zq5&_nc_ht=scontent.xx&oh=759ed307d3f575f6cd59ea2ce59529bd&oe=5E91EFA1',
                'is_carousel' => false,
                'children' => []
            ]
        ];

        $this->assertCount(4, $feed);
        $this->assertEquals($expected, $feed);
    }

    /**
     * @test
     */
    public function it_makes_multiple_calls_to_fetch_up_to_limit() {
        $profile = Profile::create(['username' => 'test user']);
        $token = AccessToken::createFromResponseArray($profile, $this->validUserWithToken());

        $expected_url = "https://graph.instagram.com/{$token->user_id}/media?fields=caption,id,media_type,media_url,thumbnail_url,permalink,children{media_type,media_url},timestamp&limit=7&access_token={$token->access_code}";

        //expected second url is copied from dummy response returned in first call
        $next_url = "https://graph.instagram.com/v1.0/17841403475633812/media?access_token=IGQVJVRkN2WHRsVi1hWkcxbVNWZA09FZAmFod1hVdXVNVmVvajFLdG5fdnA5WUFwSTdIZAUJ0MVBkWFgtYXE0TmQyeHp1cjlpaWpjeGNkUUtHak9nOFIydF9VRm1KQmlKUlRTaXlyaDNpMFR5SFUtTTYtMQZDZD&pretty=1&fields=id%2Cmedia_type%2Cmedia_url%2Ccaption%2Cthumbnail_url%2Cchildren.media_type%2Cchildren.media_url&limit=25&after=QVFIUnJpVDFsaS02bXhyUVNBSWZABLXNMMlY4MUFqb0dXREozUkNvYmlDb3JlR2RaMFhUd0puZA18waEJUVXZADbnRnV0FWR1VCbWVZARHZAONDhZAbjkxbFpESTln";

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->exactly(2))
                   ->method('get')
                   ->withConsecutive(
                       [$this->equalTo("$expected_url")],
                       [$this->equalTo("$next_url")]
                    )
                   ->willReturn($this->exampleMediaResponse($with_next_page = true));

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token, $limit = 7);

        

        $this->assertCount(7, $feed);
    }

    /**
     *@test
     */
    public function it_ignores_video_posts_if_required_in_config()
    {
        $token = AccessToken::create([
            'profile_id' => 1,
            'access_code'          => 'REFRESHED_LONG_LIVED_TOKEN',
            'username'             => 'instagram_test_username',
            'user_id'              => 'FAKE_USER_ID',
            'user_fullname'        => 'test user real name',
            'user_profile_picture' => 'https://test.test/test_pic.jpg',
        ]);


        config(['instagram-feed.ignore_video' => true]);
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($this->makeMediaUrl($token)))
                   ->willReturn($this->exampleMediaResponse());

        app()->instance(SimpleClient::class, $mockClient);
        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token);

        $expected = [
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F',
                'caption' => "test caption one",
                'permalink' => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'id' => '17853951361863258',
                'timestamp' => '',
                'thumbnail_url' => '',
                'is_carousel' => true,
                'children' => [
                    [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F",
                        "id" => "17849438098899018"
                    ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/84381272_1984995381635899_5263984109196147819_n.jpg?_nc_cat=109&_nc_sid=8ae9d6&_nc_ohc=_GH0NffaIucAX9WWveS&_nc_ht=scontent.xx&oh=0871f5013d7eff3336a8f90cc320a6d6&oe=5E921910",
                        "id" => "18132118615037332"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88164910_2338159439809338_6922195276317801534_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=UasVMdUTi0AAX-mb4IW&_nc_ht=scontent.xx&oh=4ab1132eac9766086fd268b9a80a6410&oe=5E930DAF",
                        "id" => "17894008966462830"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/89117981_2510959432554564_8299423274477931900_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=6Aodzlz0DI8AX9l8fnl&_nc_ht=scontent.xx&oh=76729707ca2daba3dddc4ca15146114f&oe=5E930DF7",
                        "id" => "17879363497541148"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/82386550_2551961055084313_287099188903912380_n.jpg?_nc_cat=100&_nc_sid=8ae9d6&_nc_ohc=9ef-YjDeuaUAX9oAc-H&_nc_ht=scontent.xx&oh=437644b122ccaa78fe7dc95de72871c7&oe=5E91CD95",
                        "id" => "17852313715894892"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/87695409_507583723499465_4522591678214389436_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=RRmJ0hA-8osAX9Y8kyy&_nc_ht=scontent.xx&oh=09dfd22f9e96fd61d6630ac3de6f413d&oe=5E90BED7",
                        "id" => "17859657748756529"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/88213032_2785656941528471_2783562451851138997_n.jpg?_nc_cat=105&_nc_sid=8ae9d6&_nc_ohc=5zHF0n4CH3kAX-WogX_&_nc_ht=scontent.xx&oh=8d4eb96ed7e60877a99e11c7bd8f98ff&oe=5E929B6B",
                        "id" => "18085866604160687"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/89060452_204421423969595_9028329592920102436_n.jpg?_nc_cat=111&_nc_sid=8ae9d6&_nc_ohc=wNX4e6_6PkIAX96Xzta&_nc_ht=scontent.xx&oh=3efb5bbf73bd4c151b14f22a8f724f85&oe=5E9068A8",
                        "id" => "18089802349183081"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/87854876_499080161046701_3462268559168413828_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=gp5kIS3kiJ8AX_lajeg&_nc_ht=scontent.xx&oh=1c90e7f4fa5f2c82e880dbd3fab1202f&oe=5E92FF1F",
                        "id" => "17906447419421821"
                      ]
                ]
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'id' => '18046738186210442',
                "permalink" => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'caption' => "test caption two",
                'timestamp' => '',
                'thumbnail_url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'is_carousel' => false,
                'children' => [],
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/73475359_561750917995932_8049459030244731697_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=Z2GNsIN-PmQAX_41ocV&_nc_ht=scontent.xx&oh=544f90b575c9fdee92f7590d16c046e7&oe=5E91D790',
                'id' => '18068269231170160',
                "permalink" => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'caption' => "test caption three",
                'timestamp' => '',
                'thumbnail_url' => '',
                'is_carousel' => true,
                'children' => [
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/73475359_561750917995932_8049459030244731697_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=Z2GNsIN-PmQAX_41ocV&_nc_ht=scontent.xx&oh=544f90b575c9fdee92f7590d16c046e7&oe=5E91D790",
                        "id" => "18049771285205991"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/75397694_3051536841540773_7421191993117550003_n.jpg?_nc_cat=101&_nc_sid=8ae9d6&_nc_ohc=6VcQl7GL0V4AX90J_Sz&_nc_ht=scontent.xx&oh=ec8753a6bb908e6df2f9033eddf7845d&oe=5E914860",
                        "id" => "17854146172710573"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/76881970_125876788524569_9194242390680601811_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=DbLu1RYIY28AX_Y_NZS&_nc_ht=scontent.xx&oh=4a9e736b9c4d04e33255238705b3daae&oe=5E91A61C",
                        "id" => "18120976165009091"
                      ],
                      [
                        "type" => "image",
                        "url" => "https://scontent.xx.fbcdn.net/v/t51.2885-15/79806151_2512679375668563_7275185319939303133_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=qGj9efDO_yMAX9r-nFK&_nc_ht=scontent.xx&oh=d62aebba7c3a698ed9060a73a54334c7&oe=5E914E87",
                        "id" => "18107139619074168"
                      ]
                ]
            ],
        ];

        $this->assertCount(3, $feed);
        $this->assertEquals($expected, $feed);
    }

    /**
     *@test
     */
    public function it_can_detect_bad_token_requests_and_throw_a_useful_exception()
    {
        $token = AccessToken::create([
            'profile_id' => 1,
            'access_code'          => 'REFRESHED_LONG_LIVED_TOKEN',
            'username'             => 'instagram_test_username',
            'user_id'              => 'FAKE_USER_ID',
            'user_fullname'        => 'test user real name',
            'user_profile_picture' => 'https://test.test/test_pic.jpg',
        ]);

        $mockHttp = $this->createMock(MockableDummyHttpClient::class);
        $mockHttp->expects($this->once())
                 ->method('get')
                 ->with($this->anything())
                 ->willThrowException($this->mockClientException());

        app()->bind(SimpleClient::class, function() use ($mockHttp) {
            return new SimpleClient($mockHttp);
        });

        $instagram = app()->make(Instagram::class);

        try {
            $instagram->fetchMedia($token);
            $this->fail('Expected to get BadTokenException');
        } catch (\Exception $e) {
            $this->assertInstanceOf(BadTokenException::class, $e);
        }

    }

    private function mockClientException()
    {
        $response = new \GuzzleHttp\Psr7\Response(200, [], json_encode([
            'meta' => [
                'code' => 400,
                'error_type' => 'OAuthAccessTokenException',
                'error_message' => 'The access_token provided is invalid'
            ]
        ]));
        
        $mock = $this->createStub(ClientException::class);
        $mock->method('getResponse')
             ->willReturn($response);


        return $mock;
    }

    private function makeMediaUrl($token)
    {
        $limit = 20;
        return sprintf(Instagram::MEDIA_URL_FORMAT, $token->user_id, Instagram::MEDIA_FIELDS, $limit, $token->access_code);
    }

}