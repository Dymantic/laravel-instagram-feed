<?php


namespace Dymantic\InstagramFeed;


use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;

class AccessTokenController
{
    public function handleRedirect()
    {
        /** @var Profile|null $profile */
        $profile = Profile::query()->find((int) request('state'));

        if (!$profile) {
            return Redirect::to(Config::get('instagram-feed.failure_redirect_to'));
        }

        try {
            $profile->requestToken(request());
        } catch (Exception $e) {
            return Redirect::to(Config::get('instagram-feed.failure_redirect_to'));
        }

        return Redirect::to(Config::get('instagram-feed.success_redirect_to'));
    }
}
