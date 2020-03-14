<?php


namespace Dymantic\InstagramFeed\Commands;


use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Instagram;
use Dymantic\InstagramFeed\Profile;
use Illuminate\Console\Command;

class RefreshTokens extends Command
{
    protected $signature = 'instagram-feed:refresh-tokens';

    protected $description = 'Refresh long lived tokens so they do not expire';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Profile::all()->filter->hasInstagramAccess()->each->refreshToken();
    }
}