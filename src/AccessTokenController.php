<?php


namespace Dymantic\InstagramFeed;


class AccessTokenController
{
    public function handleRedirect()
    {
        $profile = Profile::find((int)request('state'));

        if(! $profile) {
            return redirect(config('instagram-feed.failure_redirect_to'));
        }

        try {
            $result = $profile->requestToken(request());
        } catch(\Exception $e) {
            return redirect(config('instagram-feed.failure_redirect_to'));
        }

        return redirect(config('instagram-feed.success_redirect_to'));
    }
}