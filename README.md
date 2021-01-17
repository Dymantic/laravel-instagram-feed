# Laravel Instagram Feed

[![Build Status](https://travis-ci.org/Dymantic/laravel-instagram-feed.svg?branch=master)](https://travis-ci.org/Dymantic/laravel-instagram-feed)

## Easily include your Instagram feed(s) in your project.

The aim of this package is to make it as simple and user-friendly as possible to include your Instagram feed in your project, using Instagram's Basic Display API. The package is made so that ideally the view is almost always just using cached data, and the feed itself will be updated at a schedule of your choosing, using Laravel's great scheduling features. The feed is also designed to be resilient, so that you can safely call it from your controllers without having to worry about network errors breaking the page.

### Installation

```
composer require dymantic/laravel-instagram-feed
```

**Note** You will need to use ^v2.0, as v1 used the old Legacy API which has been shut down.

**Breaking changes from v1:** The feed now consists of entries that only contain the media type, media url, caption, id and permalink. Additionally, when completing the auth flow, the token no longer contains the users full name or avatar as the Basic Display API doesn't provide this. I am open to the idea of separately scraping for that data, but not planning on doing it right now. You will also need to refresh your tokens, which expire every 60 days. See further down for more on that.

## Tutorial

Some parts of this whole thing can be a bit confusing, especially if you are not familiar with following the OAuth flow. I have included [a tutorial](tutorial.md) to try help make things a bit more clear. Feedback or improvements on this would be most appreciated.

### Before you start

To use the Instagram Basic Display API, you will need to have a Facebook app set up with the correct permissions, etc. If you don't have this yet, head over to [the Facebook developer docs](https://developers.facebook.com/docs/instagram-basic-display-api/getting-started) and follow the instructions.

### How Instagram Media is Handled

Instagram provides three media types through this API: image, video and carousel. This package simplifies that into just a feed of images and videos. You may use the `ignore_video` config option if you don't want to include any videos. For carousel items, the first item of the carousel is used. If video is to be ignored, and the first image will be used, if it exists.

##### Note on ignoring video

If you chose to ignore video, your feed size may be smaller than the limit you requested. If you expect to be ignoring video posts you may want to increase how many posts you fetch (see "Getting the Feed" below).

### Setup

`php artisan vendor:publish` to publish the necessary migrations and config, and `php artisan migrate` to run migrations.

### Config

Publishing the vendor assets as described above will give you a `config/instagram-feed.php` file. You will need to add your config before you can get started. The comments in the file explain each of the settings.

```
// config/instagram-feed.php

<?php

return [
    /*
     * The client_id from registering your app on Instagram
     */
    'client_id'           => 'YOUR INSTAGRAM CLIENT ID',

    /*
     * The client secret from registering your app on Instagram,
     * This is not the same as an access token.
     */
    'client_secret'       => 'YOUR INSTAGRAM CLIENT SECRET',

    /*
     * The route that will respond to the Instagram callback during the OAuth process.
     * Only enter the path without the leading slash. You need to ensure that you have registered
     * a redirect_uri for your instagram app that is equal to combining the
     *  app url (from config) and this route
     */
    'auth_callback_route' => 'instagram/auth/callback',

    /*
     * On success of the OAuth process you will be redirected to this route.
     * You may use query strings to carry messages
     */
    'success_redirect_to' => 'instagram-auth-success',

    /*
     * If the OAuth process fails for some reason you will be redirected to this route.
     * You may use query strings to carry messages
     */
    'failure_redirect_to' => 'instagram-auth-failure'

    /*
     * You may filter out video media types by setting this to true. Carousel media
     * will become the first image in the carousel, and if there are no images, then
     * the entire carousel will be ignored.
     */
     'ignore_video' => false,

    /*
     * You may set an email address below if you wish to be notified of errors when
     * attempting to refresh the Instagram feed.
     */
    'notify_on_error' => null,
];
```

### Profiles

All Instagram api calls now need auth via OAuth, and so you need an entity to associate the resulting access token with. This package provides a `Dymantic\InstagramFeed\Profile` model to fit that role. An instance of the model requires a username, so you have some way to refer to it, and it is through this model that you will access the Instagram feed belonging to the access token granted to the profile. You may have several profiles, which means you may have more than one Instagram feed. How you use the Profiles is up to you (e.i. associating with users, or just having one profile, etc).

Having just a single profile for a project is a fairly common use case, so this package includes an artisan command to quickly create a profile, so that you don't need to build out the necessary UI for your users to do so. Running `php artisan instagram-feed:profile {username}` will create a profile with that username, that you may then use as desired.

### Getting Authorized

Once you have a profile, you may call the `getInstagramAuthUrl()` method on it to get a link to present to the user that will give authentication. When the user visits that url they can then grant access to your app (or not). If everything goes smoothly, the user will be redirected back to the route you configured. If access is not granted, you will be redirected to the alternate route you configured. If you have not set your client_id and/or client_secret correctly, or your Instagram app does not accept the user (because you are in Sandbox mode), Instagram won't redirect at all, and your user will see an error page from Instagram.

### Getting the feed

`Profile::feed($limit = 20)`

Once you have an authenticated profile, you may call the `feed()` method on that profile. The first time the method is called the feed will be fetched from Instagram and it will the be cached forever. Consequent calls to the `feed()` method will simply fetch from the cache. The `feed()` method can be safely called without worrying about exceptions and errors, if something goes wrong, you will just receive an empty collection.

##### Fetching all posts (no limit)

If you want to fetch all your posts (up to the last 1000), you may pass `null` as the limit parameter to your `refreshFeed` or `feed` method. The package will make multiple requests to fetch all your previous posts up to 1000. You may then use the timestamp on the posts to sort, paginate, etc.

##### Setting limits, and cache

You can set the limit for the returned media items by passing your limit to the feed method. So if you want a limit of 66, you would do: `$profile->feed(66)`. Once your feed has been fetched, it will be cached, and this result is what will be returned when future calls to the feed methods are made. This means if you want to increase your limit, for example to 88, you will have to call `$profile->refreshFeed(88)`

Remember that if you chose to ignore video, your feed size may be smaller than the limit you requested. If you expect to be ignoring video posts you may want to increase how many posts you fetch.

The feed will be a Laravel collection of items that have the following structure:

```
[
    'type' => 'image' // can be either image or video
    'url' => 'source url for media',
    'id' => 'the media id',
    'caption' => 'the media caption',
    'permalink' => 'the permalink for accessing the post',
    'timestamp' => 'the timestamp of the post',
]
```

### Updating the feed

`Profile::refreshFeed($limit = 20)`

Obviously the feed needs to be updated, which is exactly what the `refreshFeed()` method on a Profile instance does. This method will return the same kind of collection as the `feed()` method if successful. However, this method will throw an Exception if one happens to occur (network failure, invalid token, etc).

This package includes an artisan command `php artisan instagram-feed:refresh`, that will refresh all authorised profiles, and handle errors if they occur. If you have an email address set in the config, that address will be notified in the case of an error. It is recommended to use Laravel's scheduling features to run this command as frequently as you see fit.

### Refreshing access tokens

The long lived access tokens for the API expire after 60 days. This package includes an artisan command that will handle this for you, you just need to ensure that it runs at least once every 60 days. The command is `php artisan instagram-feed:refresh-tokens`
