<?php


namespace Dymantic\InstagramFeed\Commands;


use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Instagram;
use Dymantic\InstagramFeed\Profile;
use Illuminate\Console\Command;

class RefreshMediaCount extends Command
{
    protected $signature = 'instagram-feed:refresh-media-count';

    protected $description = 'Refresh media counts';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Profile::all()->filter->hasInstagramAccess()->each->refreshMediaCount();
    }
}
