## Upgrade Guide

### v2.* -> v3.*

#### Migration for `dymantic_instagram_basic_profiles` table

You will need to add a new column onto the `dymantic_instagram_basic_profiles` table.

````php

class UpdateInstagramBasicProfileTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('dymantic_instagram_basic_profiles', function (Blueprint $table) {
            $table->string('identity_token')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('dymantic_instagram_basic_profiles', function (Blueprint $table) {
            $table->dropColumns('identity_token');
        });
    }
}
````

#### Update to use new feed

The feed is no longer a `Collection` instance of simple array items. You may still iterate over the feed using `@foreach` in blade views as normal, but each item is now an instance of the `InstagramMedia` class, and its data needs to be accessed accordingly. For example, in v2 you would use something like `$post['url']`, and in v3 you would have to use `$post->url`.

You can either update your views to use the object syntax, or you can do the following to continue using the array syntax:

````php
$feed->collect()->map->toArray();
````
