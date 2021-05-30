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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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

        Http::fake([
            Instagram::REQUEST_ACCESS_TOKEN_URL => Http::response($this->validTokenDetails()),
        ]);

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

        Http::fake([
            "https://graph.instagram.com/FAKE_USER_ID?fields=id,username&access_token=VALID_ACCESS_TOKEN" => Http::response($this->validUserDetails()),
        ]);

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

        $url = "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=TEST_CLIENT_SECRET&access_token=VALID_ACCESS_TOKEN";
        Http::fake([
            $url => Http::response($this->validLongLivedToken()),
        ]);

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
        $url = "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=VALID_LONG_LIVED_TOKEN";
        Http::fake([
            $url => Http::response($this->refreshedLongLivedToken()),
        ]);

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

        $expected_url = sprintf(Instagram::MEDIA_URL_FORMAT, $token->user_id, Instagram::MEDIA_FIELDS, 88, $token->access_code);

        Http::fake([
            'https://graph.instagram.com/*' => Http::response($this->exampleMediaResponse()),
        ]);

        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token, $limit = 88);

        Http::assertSent(function(Request $request) use ($expected_url) {
            return urldecode($request->url()) === $expected_url;
        });

        $expected = [
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F',
                'caption' => "test caption one",
                'id' => '17853951361863258',
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'id' => '18046738186210442',
                'caption' => "test caption two",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
            ],
            [
                'type' => 'video',
                'url' => 'https://video.xx.fbcdn.net/v/t50.2886-16/80075364_501004160505270_3520263354313331489_n.mp4?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=fXXNJuZcyXEAX8rD8l8&_nc_ht=video.xx&oh=e1cbd15a0f23db1f7d5a5f6ddd2ace83&oe=5E9400C7',
                'id' => '18068269231170160',
                'caption' => "test caption three",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
            ],
            [
                'type' => 'video',
                'url' => 'https://video.xx.fbcdn.net/v/t50.2886-16/79391351_481629065798947_3744187809422239413_n.mp4?_nc_cat=102&vs=18083652679083225_3607239704&_nc_vs=HBkcFQAYJEdIZHF1d1FqWldFQkNyWUJBTFhtOWFENUJ2WXpia1lMQUFBRhUAACgAGAAbAYgHdXNlX29pbAExFQAAGAAWsrTUvY%2B%2Fn0AVAigCQzMsF0Ay90vGp%2B%2BeGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHXqBwA%3D&_nc_sid=59939d&efg=eyJ2ZW5jb2RlX3RhZyI6InZ0c192b2RfdXJsZ2VuLjcyMGZlZWQifQ%3D%3D&_nc_ohc=fO8GgEnZ468AX_Fk0Ib&_nc_ht=video.xx&oh=1731b5b44ac7a430e1f90596c15806c3&oe=5E916311&_nc_rid=d477a9015c',
                'id' => '18033634498224799',
                'caption' => "test caption four",
                'permalink' => 'https://www.instagram.com/p/Ab12CDeFgHi/',
                'timestamp' => '',
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

        $expected_initial_url = "https://graph.instagram.com/{$token->user_id}/media?fields=caption,id,media_type,media_url,thumbnail_url,permalink,children{media_type,media_url},timestamp&limit=7&access_token={$token->access_code}";

        //expected second url is copied from dummy response returned in first call
        $next_url = "https://graph.instagram.com/v1.0/17841403475633812/media?access_token=IGQVJVRkN2WHRsVi1hWkcxbVNWZA09FZAmFod1hVdXVNVmVvajFLdG5fdnA5WUFwSTdIZAUJ0MVBkWFgtYXE0TmQyeHp1cjlpaWpjeGNkUUtHak9nOFIydF9VRm1KQmlKUlRTaXlyaDNpMFR5SFUtTTYtMQZDZD&pretty=1&fields=id%2Cmedia_type%2Cmedia_url%2Ccaption%2Cthumbnail_url%2Cchildren.media_type%2Cchildren.media_url&limit=25&after=QVFIUnJpVDFsaS02bXhyUVNBSWZABLXNMMlY4MUFqb0dXREozUkNvYmlDb3JlR2RaMFhUd0puZA18waEJUVXZADbnRnV0FWR1VCbWVZARHZAONDhZAbjkxbFpESTln";

        Http::fake([
            '*' => Http::response($this->exampleMediaResponse($with_next_page = true)),
        ]);

        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token, $limit = 7);

        Http::assertSent(function(Request $request) use ($expected_initial_url) {
            return urldecode($request->url()) === $expected_initial_url;
        });

        Http::assertSent(function(Request $request) use ($next_url) {
            return $request->url() === $next_url;
        });

        

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

        Http::fake([
            '*' => Http::response($this->exampleMediaResponse()),
        ]);

        $instagram = app(Instagram::class);

        $feed = $instagram->fetchMedia($token);

        Http::assertSent(fn (Request $r) => urldecode($r->url()) === $this->makeMediaUrl($token));

        $expected = [
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/88377911_489796465235615_7665986482865453688_n.jpg?_nc_cat=103&_nc_sid=8ae9d6&_nc_ohc=yrRAJXdvYI4AX9FZA2-&_nc_ht=scontent.xx&oh=8f5c3ce9f043abfb31fc8b21aefc433e&oe=5E93D95F',
                'caption' => "test caption one",
                'permalink' => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'id' => '17853951361863258',
                'timestamp' => '',
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/80549905_2594006480669195_8926697910974014198_n.jpg?_nc_cat=104&_nc_sid=8ae9d6&_nc_ohc=vLLm_GgfP60AX8td-AL&_nc_ht=scontent.xx&oh=96a59075b998f800c3b1321a6d87b90c&oe=5E915974',
                'id' => '18046738186210442',
                "permalink" => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'caption' => "test caption two",
                'timestamp' => '',
            ],
            [
                'type' => 'image',
                'url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/73475359_561750917995932_8049459030244731697_n.jpg?_nc_cat=107&_nc_sid=8ae9d6&_nc_ohc=Z2GNsIN-PmQAX_41ocV&_nc_ht=scontent.xx&oh=544f90b575c9fdee92f7590d16c046e7&oe=5E91D790',
                'id' => '18068269231170160',
                "permalink" => "https://www.instagram.com/p/Ab12CDeFgHi/",
                'caption' => "test caption three",
                'timestamp' => '',
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

        Http::fake([
            '*' => Http::response(['meta' => ['error_type' => 'OAuthAccessTokenException']], 400),
        ]);

        $instagram = app()->make(Instagram::class);

        try {
            $instagram->fetchMedia($token);
            $this->fail('Expected to get BadTokenException');
        } catch (\Exception $e) {
            $this->assertInstanceOf(BadTokenException::class, $e);
        }

    }

    private function makeMediaUrl($token)
    {
        $limit = 20;
        return sprintf(Instagram::MEDIA_URL_FORMAT, $token->user_id, Instagram::MEDIA_FIELDS, $limit, $token->access_code);
    }

}