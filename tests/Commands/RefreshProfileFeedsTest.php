<?php

namespace Dymantic\InstagramFeed\Tests\Commands;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Mail\FeedRefreshFailed;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class RefreshProfileFeedsTest extends TestCase
{
    use FakesInstagramCalls;
    /**
     *@test
     */
    public function calling_the_command_will_refresh_the_feeds()
    {
        $profileA = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($profileA, $this->validTokenDetails());
        $profileB = Profile::create(['username' => 'test user two']);
        AccessToken::createFromResponseArray($profileB, $this->validTokenDetails());

        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->exactly(2))
                   ->method('get')
                   ->withConsecutive(
                       $this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profileA->fresh()->accessToken()}"),
                       $this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$profileB->fresh()->accessToken()}"))
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        Artisan::call('instagram-feed:refresh');

        $this->assertTrue(cache()->has($profileA->cacheKey()));
        $this->assertTrue(cache()->has($profileB->cacheKey()));

    }

    /**
     *@test
     */
    public function non_authorized_profiles_are_not_refreshed()
    {
        $authorized_profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($authorized_profile, $this->validTokenDetails());
        $unauthorized_profile = Profile::create(['username' => 'test user two']);

        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with(
                       $this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$authorized_profile->fresh()->accessToken()}")
                   )
                   ->willReturn($this->exampleMediaResponse());

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        Artisan::call('instagram-feed:refresh');

        $this->assertTrue(cache()->has($authorized_profile->cacheKey()));
        $this->assertFalse(cache()->has($unauthorized_profile->cacheKey()));
    }

    /**
     *@test
     */
    public function an_email_will_be_sent_if_an_error_occurs_in_refreshing()
    {
        Mail::fake();

        $authorized_profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($authorized_profile, $this->validTokenDetails());

        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');
        $this->app['config']->set('instagram-feed.notify_on_error', 'test@test.con');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with(
                       $this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$authorized_profile->fresh()->accessToken()}")
                   )
                   ->willThrowException(new \Exception(''));

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        Artisan::call('instagram-feed:refresh');

        $this->assertFalse(cache()->has($authorized_profile->cacheKey()));

        Mail::assertSent(FeedRefreshFailed::class, function($mail) {
            return $mail->hasTo('test@test.con');
        });
    }

    /**
     *@test
     */
    public function a_profile_with_an_expired_or_invalid_token_will_have_its_token_deleted()
    {
        Mail::fake();

        $authorized_profile = Profile::create(['username' => 'test user']);
        AccessToken::createFromResponseArray($authorized_profile, $this->validTokenDetails());

        $this->app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $this->app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $this->app['config']->set('instagram-feed.auth_callback_route', 'instagram');
        $this->app['config']->set('instagram-feed.notify_on_error', 'test@test.con');

        $mockClient = $this->createMock(SimpleClient::class);
        $mockClient->expects($this->once())
                   ->method('get')
                   ->with(
                       $this->equalTo("https://api.instagram.com/v1/users/self/media/recent/?access_token={$authorized_profile->fresh()->accessToken()}")
                   )
                   ->willThrowException(new BadTokenException(''));

        $this->app->bind(SimpleClient::class, function() use ($mockClient) {
            return $mockClient;
        });

        Artisan::call('instagram-feed:refresh');

        $this->assertFalse(cache()->has($authorized_profile->cacheKey()));

        $this->assertFalse($authorized_profile->fresh()->hasInstagramAccess());
        $this->assertEquals(0, AccessToken::count());
    }
}