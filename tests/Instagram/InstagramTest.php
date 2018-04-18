<?php

namespace Dymantic\InstagramFeed\Tests\Instagram;

use Dymantic\InstagramFeed\Instagram;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\TestCase;

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

}