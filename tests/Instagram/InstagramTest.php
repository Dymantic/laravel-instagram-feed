<?php

namespace Dymantic\InstagramFeed\Tests\Instagram;

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

    /**
     * @test
     */
    public function it_can_provide_a_auth_url_for_a_given_profile()
    {
        $profile = Profile::create(['username' => 'test_user']);

        $full_redirect_uri = 'http://test.test/instagram?profile=' . $profile->id;

        $expected = "https://api.instagram.com/oauth/authorize/?client_id=TEST_CLIENT_ID&redirect_uri=$full_redirect_uri&response_type=code";

        $instagram = new Instagram([
            'client_id'     => 'TEST_CLIENT_ID',
            'client_secret' => 'TEST_CLIENT_SECRET',
            'auth_callback_route'  => 'instagram'
        ], app()->make(SimpleClient::class));

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
                       'redirect_uri'  => "http://test.test/instagram?profile={$profile->id}",
                       'code'          => 'TEST_REQUEST_CODE'
                   ]))
                   ->willReturn($this->validTokenDetails());

        $instagram = new Instagram([
            'client_id'     => 'TEST_CLIENT_ID',
            'client_secret' => 'TEST_CLIENT_SECRET',
            'auth_callback_route'  => 'instagram'
        ], $mockClient);


        $this->assertEquals(
            $this->validTokenDetails(),
            $instagram->requestTokenForProfile($profile, $this->successAuthRequest())
        );
    }

    /**
     *@test
     */
    public function it_can_fetch_media_for_a_given_access_code()
    {
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token=TEST_ACCESS_CODE"))
                   ->willReturn($this->exampleMediaResponse());

        $instagram = new Instagram([
            'client_id'     => 'TEST_CLIENT_ID',
            'client_secret' => 'TEST_CLIENT_SECRET',
            'auth_callback_route'  => 'instagram'
        ], $mockClient);

        $feed = $instagram->fetchMedia('TEST_ACCESS_CODE');

        $expected = [
            [
                'low' => 'http://distillery.s3.amazonaws.com/media/2011/02/02/6ea7baea55774c5e81e7e3e1f6e791a7_6.jpg',
                'thumb' => 'http://distillery.s3.amazonaws.com/media/2011/02/02/6ea7baea55774c5e81e7e3e1f6e791a7_5.jpg',
                'standard' => 'http://distillery.s3.amazonaws.com/media/2011/02/02/6ea7baea55774c5e81e7e3e1f6e791a7_7.jpg',
                'likes' => 15,
                'caption' => 'Inside le truc #foodtruck'
            ],
            [
                'low' => 'http://distilleryimage2.ak.instagram.com/11f75f1cd9cc11e2a0fd22000aa8039a_6.jpg',
                'thumb' => 'http://distilleryimage2.ak.instagram.com/11f75f1cd9cc11e2a0fd22000aa8039a_5.jpg',
                'standard' => 'http://distilleryimage2.ak.instagram.com/11f75f1cd9cc11e2a0fd22000aa8039a_7.jpg',
                'likes' => 1,
                'caption' => ''
            ]
        ];

        $this->assertCount(2, $feed);
        $this->assertEquals($expected, $feed);
    }

    /**
     *@test
     */
    public function it_can_detect_bad_token_requests_and_throw_a_useful_exception()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

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
            $instagram->fetchMedia('TEST_ACCESS_CODE');
            $this->fail('Expected to get BadTokenException');
        } catch (\Exception $e) {
            $this->assertInstanceOf(BadTokenException::class, $e);
        }

    }

    private function mockClientException()
    {
        $mock = $this->createMock(ClientException::class);
        $mock->expects($this->once())
             ->method('getResponse')
             ->willReturn(new class {
                 public function getBody() {
                     return json_encode([
                         'meta' => [
                             'code' => 400,
                             'error_type' => 'OAuthAccessTokenException',
                             'error_message' => 'The access_token provided is invalid'
                         ]
                     ]);
                 }
             });

        return $mock;
    }

}