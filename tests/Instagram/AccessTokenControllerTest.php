<?php


namespace Dymantic\InstagramFeed\Tests\Instagram;


use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Instagram;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $this->withoutExceptionHandling();
        $this->app['config']->set('instagram-feed.success_redirect_to', 'success');

        $get_calls = 0;
        Http::fake(function(Request $request) use (&$get_calls) {
            if($request->method() === 'POST') {
                return $this->validTokenDetails();
            }
            $get_calls++;
            return $get_calls === 1 ? $this->validUserDetails() : $this->validLongLivedToken();
        });

        $profile = Profile::create(['username' => 'test_user']);
        $profile_identifier = $this->getProfileIdentifier($profile);


        $redirect_url = config('instagram-feed.auth_callback_route') . "?code=TEST_REQUEST_TOKEN&state={$profile_identifier}";

        $response = $this->get($redirect_url);
        $response->assertRedirect('success');

        $this->assertTrue($profile->fresh()->hasInstagramAccess());
        $this->assertCount(1, AccessToken::all());
        $this->assertNull($profile->fresh()->identity_token);
    }

    /**
     *@test
     */
    public function it_redirects_if_it_cannot_resolve_the_profile_from_the_redirect()
    {
        $this->withoutExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');

        Http::fake();
        Log::shouldReceive('error')->once()->with('unable to retrieve IG profile');

        $redirect_url = config('instagram-feed.auth_callback_route') . "?&code=TEST_REQUEST_TOKEN&state=BAD_TOKEN";

        $response = $this->get($redirect_url);
        $response->assertRedirect('failed_auth');

        Http::assertNothingSent();

        $this->assertCount(0, AccessToken::all());
    }


    /**
     *@test
     */
    public function it_redirects_if_it_fails_to_get_request_token()
    {
        $this->withoutExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');

        Http::fake();

        Log::shouldReceive('error')->once()->with('Unable to get request token');

        $profile = Profile::create(['username' => 'test_user']);
        $profile_identifier = $this->getProfileIdentifier($profile);

        $redirect_url = config('instagram-feed.auth_callback_route') . "?state={$profile_identifier}&error=access_denied";

        $response = $this->get($redirect_url);
        $response->assertRedirect(config('instagram-feed.failed_auth'));

        Http::assertNothingSent();

        $this->assertCount(0, AccessToken::all());
    }

    /**
     *@test
     */
    public function it_redirects_if_it_fails_to_get_an_access_token()
    {
        $this->withoutExceptionHandling();
        $this->app['config']->set('instagram-feed.failure_redirect_to', 'failed_auth');

        $profile = Profile::create(['username' => 'test_user']);
        $profile_identifier = $this->getProfileIdentifier($profile);

        Http::fake([
            Instagram::REQUEST_ACCESS_TOKEN_URL => Http::response([
                'error_message' => 'bad test request'
            ], 400)
        ]);

        $expected_error = sprintf(
            "Http request to %s failed with a status of %d and error message: %s",
            Instagram::REQUEST_ACCESS_TOKEN_URL, 400, "bad test request"
        );
        Log::shouldReceive('error')->once()->with($expected_error);

        $redirect_url = config('instagram-feed.auth_callback_route') . "?code=TEST_REQUEST_CODE&state={$profile_identifier}";

        $response = $this->get($redirect_url);
        $response->assertRedirect(config('instagram-feed.failure_redirect_to'));

        Http::assertSent(fn (Request $request) => $request->url() == Instagram::REQUEST_ACCESS_TOKEN_URL);

        $this->assertCount(0, AccessToken::all());
    }

    private function getProfileIdentifier($profile)
    {
        return Str::after($profile->getInstagramAuthUrl(), '&state=');
    }




}