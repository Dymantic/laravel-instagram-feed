<?php


namespace Dymantic\InstagramFeed\Commands;


use Dymantic\InstagramFeed\Profile;
use Illuminate\Console\Command;

class CreateBasicProfile extends Command
{
    protected $signature = 'instagram-feed:profile {username}';

    protected $description = 'Creates a basic profile that can have an Instagram feed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Profile::create(['username' => $this->argument('username')]);
    }
}