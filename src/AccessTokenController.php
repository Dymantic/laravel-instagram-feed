<?php


namespace Dymantic\InstagramFeed;


use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class AccessTokenController
{
    public function handleRedirect(Request $request)
    {
        /** @var Profile|null $profile */
        $profile = Profile::query()->find((int) $request->input('state'));

        if (!$profile) {
            Log::error('unable to retrieve IG profile');
            return Redirect::to(Config::get('instagram-feed.failure_redirect_to'));
        }

        try {
            $profile->requestToken($request);
        } catch (Exception $e) {
            return Redirect::to(Config::get('instagram-feed.failure_redirect_to'));
        }

        return Redirect::to(Config::get('instagram-feed.success_redirect_to'));
    }
}
