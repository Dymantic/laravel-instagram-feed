<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Commands\CreateBasicProfile;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class InstagramFeedServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if($this->app->runningInConsole()) {
            $this->commands([
                CreateBasicProfile::class
            ]);
        }
    }

    public function register()
    {
        $this->app->bind(Instagram::class, function($app) {
            return new Instagram(config('instagram-feed'), $app->make(SimpleClient::class));
        });

        $this->app->bind(SimpleClient::class, function($app) {
            return new SimpleClient(new Client());
        });
    }
}