<?php

namespace Dymantic\InstagramFeed\Tests\Profiles;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Exceptions\AccessTokenRequestException;
use Dymantic\InstagramFeed\Exceptions\RequestTokenException;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class ProfilesTest extends TestCase
{
    use FakesInstagramCalls;

    /**
     * @test
     */
    public function a_basic_profile_can_be_created_via_command()
    {
        Artisan::call('instagram-feed:profile', ['username' => 'test_username']);

        $this->assertDatabaseHas('dymantic_instagram_basic_profiles', ['username' => 'test_username']);
    }

    /**
     * @test
     */
    public function the_profile_username_needs_to_be_unique()
    {
        $existing_profile = Profile::create(['username' => 'test_user']);

        try {
            Artisan::call('instagram-feed:profile', ['username' => $existing_profile->username]);
            $this->fail('Should not get here');
        } catch (\Exception $e) {
            $this->assertCount(1, Profile::all());
        }
    }

    /**
     * @test
     */
    public function a_profile_can_generate_the_correct_auth_init_url()
    {
        $client_id = 'TEST_CLIENT_ID';
        $redirect_uri_base = 'instagram_test';

        $this->app['config']->set('instagram-feed.client_id', $client_id);
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', $redirect_uri_base);
        $profile = Profile::create(['username' => 'test_user']);


        $app_url = rtrim(config('app.url'), '/');
        $full_redirect_uri = "{$app_url}/{$redirect_uri_base}?profile={$profile->id}";

        $expected = "https://api.instagram.com/oauth/authorize/?client_id=$client_id&redirect_uri=$full_redirect_uri&response_type=code";

        $this->assertEquals($expected, $profile->getInstagramAuthUrl());
    }

    /**
     * @test
     */
    public function a_profile_can_request_an_access_token_from_a_successful_auth_redirect()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

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

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        $profile->requestToken($this->successAuthRequest());

        $this->assertDatabaseHas('dymantic_instagram_feed_tokens', [
            'profile_id'           => $profile->id,
            'access_code'          => 'TEST_TOKEN_CODE',
            'username'             => 'TEST_USERNAME',
            'user_id'              => 'TEST ID',
            'user_fullname'        => 'TEST FULL NAME',
            'user_profile_picture' => 'TEST AVATAR'
        ]);
    }

    /**
     * @test
     */
    public function requesting_a_token_from_a_denied_auth_redirect_throws_an_exception()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'https://test_instagram.test/instagram');

        $profile = Profile::create(['username' => 'test_user']);

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->never())
                   ->method('post');

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        try {
            $profile->requestToken($this->deniedAuthRequest());

            $this->fail('Should have thrown request token exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(RequestTokenException::class, $e);
        }
    }

    /**
     * @test
     */
    public function any_exception_raised_by_requesting_access_token_will_be_caught_and_thrown_as_access_token_exception(
    )
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

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
                   ->willThrowException(new \Exception());

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        try {
            $profile->requestToken($this->successAuthRequest());

            $this->fail('Should have thrown AccessTokenRequestException');
        } catch (\Exception $e) {
            $this->assertInstanceOf(AccessTokenRequestException::class, $e);
        }
    }

    /**
     * @test
     */
    public function a_profile_that_has_a_token_on_record_is_considered_to_have_instagram_access()
    {
        $profile_with_token = Profile::create(['username' => 'profile one']);
        $profile_without_token = Profile::create(['username' => 'profile two']);

        AccessToken::createFromResponseArray($profile_with_token, $this->validTokenDetails());

        $this->assertTrue($profile_with_token->fresh()->hasInstagramAccess());
        $this->assertFalse($profile_without_token->fresh()->hasInstagramAccess());
    }

    /**
     * @test
     */
    public function a_profile_can_return_its_access_token_string()
    {
        $profile = Profile::create(['username' => 'test_user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $this->assertEquals('TEST_TOKEN_CODE', $profile->fresh()->accessToken());
    }

    /**
     * @test
     */
    public function a_profile_with_an_access_token_can_fetch_its_recent_media()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test_user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $mockCLient = $this->createMock(SimpleClient::class);
        $mockCLient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profile->fresh()->accessToken()}"))
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function () use ($mockCLient) {
            return $mockCLient;
        });

        $feed = $profile->feed();

        $this->assertCount(2, $feed);
    }

    /**
     *@test
     */
    public function the_feed_is_returned_as_a_collection()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test_user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $mockCLient = $this->createMock(SimpleClient::class);
        $mockCLient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profile->fresh()->accessToken()}"))
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function () use ($mockCLient) {
            return $mockCLient;
        });

        $feed = $profile->feed();

        $this->assertInstanceOf(Collection::class, $feed);
    }

    /**
     * @test
     */
    public function the_profile_has_a_cache_key()
    {
        $profile = Profile::create(['username' => 'test_user']);

        $this->assertEquals(Profile::CACHE_KEY_BASE . ":" . $profile->id, $profile->cacheKey());
    }

    /**
     * @test
     */
    public function the_profile_feed_is_cached()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test_user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $mockCLient = $this->createMock(SimpleClient::class);
        $mockCLient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profile->fresh()->accessToken()}"))
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function () use ($mockCLient) {
            return $mockCLient;
        });

        $feed = $profile->feed();
        $this->assertCount(2, $feed);

        $this->assertTrue(cache()->has($profile->cacheKey()));
        $this->assertEquals($feed->all(), cache()->get($profile->cacheKey()));

        $second_call_to_feed = $profile->feed();

        $this->assertEquals($feed->all(), $second_call_to_feed->all());
    }

    /**
     * @test
     */
    public function the_feed_for_a_profile_can_be_refreshed()
    {
        $old_feed = [
            ['low' => 'test_low', 'thumb' => 'test_thumb', 'standard' => 'test_standard']
        ];

        $profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        cache()->put($profile->cacheKey(), $old_feed, 1000);

        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profile->fresh()->accessToken()}"))
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        $feed = $profile->refreshFeed();
        $this->assertCount(2, $feed);
        $this->assertEquals($feed->all(), cache()->get($profile->cacheKey()));
    }

    /**
     * @test
     */
    public function a_profile_can_clear_its_token()
    {
        $profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $this->assertTrue($profile->fresh()->hasInstagramAccess());

        $profile->clearToken();

        $this->assertFalse($profile->fresh()->hasInstagramAccess());
        $this->assertEquals(0, AccessToken::count());
    }

    /**
     * @test
     */
    public function the_feed_method_will_not_throw_exceptions_but_only_return_empty_collection()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test_user']);

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->anything())
                   ->willThrowException(new \Exception());

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        $feed = $profile->feed();
        $this->assertEquals([], $feed->all());
    }

    /**
     * @test
     */
    public function the_refresh_feed_method_will_not_overwrite_cache_with_failed_response()
    {
        $this->disableExceptionHandling();
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test_user']);
        cache()->forever($profile->cacheKey(), ['test' => 'test value']);

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with($this->anything())
                   ->willThrowException(new \Exception());

        app()->bind(SimpleClient::class, function () use ($mockClient) {
            return $mockClient;
        });

        try {
            $profile->refreshFeed();
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertEquals(['test' => 'test value'], cache()->get($profile->cacheKey()));
        }

    }

    /**
     * @test
     */
    public function a_profile_can_present_its_view_data()
    {
        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($profile, $this->validTokenDetails());

        $expected = [
            'name'     => 'test user',
            'username' => 'TEST_USERNAME',
            'fullname' => 'TEST FULL NAME',
            'avatar'   => 'TEST AVATAR',
            'has_auth' => true,
            'get_auth_url' => $profile->getInstagramAuthUrl()
        ];

        $this->assertEquals($expected, $profile->viewData());
    }
}