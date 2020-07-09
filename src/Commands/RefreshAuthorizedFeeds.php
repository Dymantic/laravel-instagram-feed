<?php


namespace Dymantic\InstagramFeed\Commands;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Mail\FeedRefreshFailed;
use Dymantic\InstagramFeed\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RefreshAuthorizedFeeds extends Command
{
    protected $signature = 'instagram-feed:refresh';

    protected $description = 'Refreshes all the authorized feeds';

    public function handle()
    {
        Profile::all()->filter(function ($profile) {
            return $profile->hasInstagramAccess();
        })->each(function ($profile) {
            try {
                $profile->refreshFeed();
            } catch (\Exception $e) {
                if ($e instanceof BadTokenException) {
                    $profile->clearToken();
                }

                if(config('instagram-feed.notify_on_error', null) != 'null') {
                    Mail::to(
                        config('instagram-feed.notify_on_error'))->send(new FeedRefreshFailed($profile->fresh(), $e->getMessage())
                    );
                }
            }
        });
    }
}