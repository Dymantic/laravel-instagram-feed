<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Commands\CreateBasicProfile;
use Dymantic\InstagramFeed\Commands\RefreshAuthorizedFeeds;
use Dymantic\InstagramFeed\Commands\RefreshTokens;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;

class InstagramFeedServiceProvider extends ServiceProvider
{

    public function boot(Filesystem $filesystem)
    {

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateBasicProfile::class,
                RefreshAuthorizedFeeds::class,
                RefreshTokens::class,
            ]);
        }

        if (!class_exists('CreateInstagramFeedTokenTable') && ! $this->migrationAlreadyPublished($filesystem, '_create_instagram_feed_token_table.php')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_instagram_feed_token_table.php.stub' => database_path('migrations/' . date('Y_m_d_His',
                        time()) . '_create_instagram_feed_token_table.php'),
            ], 'migrations');
        }

        if (!class_exists('CreateInstagramBasicProfileTable') && ! $this->migrationAlreadyPublished($filesystem, '_create_instagram_basic_profile_table.php')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_instagram_basic_profile_table.php.stub' => database_path('migrations/' . date('Y_m_d_His',
                        time()) . '_create_instagram_basic_profile_table.php'),
            ], 'migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->loadViewsFrom(__DIR__ . '/../views', 'instagram-feed');

        $this->publishes([
            __DIR__ . '/../config/instagram-feed.php' => config_path('instagram-feed.php')
        ]);

    }

    public function register()
    {
        $this->app->bind(Instagram::class, function ($app) {
            return new Instagram(config('instagram-feed'), $app->make(SimpleClient::class));
        });

        $this->app->bind(SimpleClient::class, function ($app) {
            return new SimpleClient(new Client());
        });

    }

    /**
     * @param Filesystem $filesystem
     * @param $filename
     * @return bool
     */
    protected function migrationAlreadyPublished(Filesystem $filesystem, $filename): bool
    {
        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
                ->flatMap(function ($path) use ($filesystem, $filename) {
                    return $filesystem->glob($path.'*'. $filename);
                })
                ->count() > 0;
    }
}
