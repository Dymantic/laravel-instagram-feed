<?php

namespace Dymantic\InstagramFeed\Tests;

use Dymantic\InstagramFeed\InstagramFeedServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Exceptions\Handler;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{

    public function setUp() :void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct() {}

            public function report(\Exception $e)
            {
                // no-op
            }

            public function render($request, \Exception $e) {
                throw $e;
            }

        });
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            InstagramFeedServiceProvider::class
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('instagram-feed.auth_callback_route', 'instagram');
        $app['config']->set('app.url', 'http://test.test');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');

        $app['config']->set('instagram-feed.client_id', 'TEST_CLIENT_ID');
        $app['config']->set('instagram-feed.client_secret', 'TEST_CLIENT_SECRET');
        $app['config']->set('instagram-feed.auth_callback_route', 'instagram');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('nameless_authors', function (Blueprint $table) {
            $table->increments('id');
        });

        TestUser::create(['name' => 'test user', 'email' => 'test@example.com', 'password' => 'password']);

        include_once __DIR__ . '/../database/migrations/create_instagram_feed_token_table.php.stub';
        include_once __DIR__ . '/../database/migrations/create_instagram_basic_profile_table.php.stub';

        (new \CreateInstagramFeedTokenTable())->up();
        (new \CreateInstagramBasicProfileTable())->up();
    }
}