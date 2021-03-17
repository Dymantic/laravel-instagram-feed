<?php


namespace Dymantic\InstagramFeed\Commands;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Mail\FeedRefreshFailed;
use Dymantic\InstagramFeed\Profile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class RefreshAuthorizedFeeds extends Command
{
    protected $signature = 'instagram-feed:refresh';

    protected $description = 'Refreshes all the authorized feeds';

    public function handle()
    {
        Profile::all()
            ->filter(function ($profile) {
                return $profile->hasInstagramAccess();
            })
            ->each(function ($profile) {
                try {
                    $profile->refreshFeed();
                } catch (Exception $e) {
                    if ($e instanceof BadTokenException) {
                        $profile->clearToken();
                    }

                    if (!empty($address = Config::get('instagram-feed.notify_on_error'))) {
                        Mail::to($address)
                            ->send(new FeedRefreshFailed($profile->fresh(), $e->getMessage())
                        );
                    }
                }
            });
    }
}
