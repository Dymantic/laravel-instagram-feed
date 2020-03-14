<?php


namespace Dymantic\InstagramFeed\Tests\Instagram;


use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;

class AccessTokenControllerTest extends TestCase
{
    use FakesInstagramCalls;
    /**
     *@test
     */
    public function the_route_for_the_instagram_auth_flow_redirect_is_registered()
    {
        $response = $this->get(config('instagram-feed.auth_callback_route'));

        $this->assertNotEquals(404, $response->getStatusCode());
    }

    /**
     *@test
     */
    public function it_handles_a_redirect_with_a_valid_profile_id_and_code()
    {
        $this->disableExceptionHandling();
        $this->app['config']->set('instagram-feed.success_redirect_to', 'success');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->at(0))
            ->method('post')
            ->with($this->anything(), $this->anything())
            ->willReturn($this->validTokenDetails());

        $mockClient->expects($this->at(1))
                   ->method('get')
                   ->with($this->anything())
                   ->willReturn($this->validUserDetails());

        $mockClient->expects($this->at(2))
                   ->method('get')
                   ->with($this->anything())
                   ->willReturn($this->validLongLivedToken());

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        $profile = Profile::create(['username' => 'test_user']);

        $redirect_url = config('instagram-feed.auth_callback_route') . "?code=TEST_REQUEST_TOKEN&state={$profile->id}";

        $response = $this->get($redirect_url);
        $response->assertRedirect('success');

        $this->assertTrue($profile->fresh()->hasInstagramAccess());
        $this->assertCount(1, AccessToken::all());
    }

    /**
     *@test
     */
    public function it_redirects_if_it_cannot_resolve_the_profile_from_the_redirect()
    {
        $this->disableExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->never())
                   ->method('post');

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });


        $redirect_url = config('instagram-feed.auth_callback_route') . "?profile=FAKE&code=TEST_REQUEST_TOKEN";

        $response = $this->get($redirect_url);
        $response->assertRedirect(config('instagram-feed.failure_redirect_to'));

        $this->assertCount(0, AccessToken::all());
    }

    /**
     *@test
     */
    public function it_redirects_if_it_fails_to_get_request_token()
    {
        $this->disableExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');
        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->never())
                   ->method('post');

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        $profile = Profile::create(['username' => 'test_user']);

        $redirect_url = config('instagram-feed.auth_callback_route') . "?&error=access_denied";

        $response = $this->get($redirect_url);
        $response->assertRedirect(config('instagram-feed.failure_redirect_to'));

        $this->assertCount(0, AccessToken::all());
    }

    /**
     *@test
     */
    public function it_redirects_if_it_fails_to_get_an_access_token()
    {
        $this->disableExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');

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
                   ->willThrowException(new \Exception());

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });


        $redirect_url = config('instagram-feed.auth_callback_route') . "?code=TEST_REQUEST_CODE&state={$profile->id}";

        $response = $this->get($redirect_url);
        $response->assertRedirect(config('instagram-feed.failure_redirect_to'));

        $this->assertCount(0, AccessToken::all());
    }


}