# Laravel Instagram Feed

[![Build Status](https://travis-ci.org/Dymantic/laravel-instagram-feed.svg?branch=master)](https://travis-ci.org/Dymantic/laravel-instagram-feed)

## Easily include your Instagram feed(s) in your project.

The aim of this package is to make it as simple and user-friendly as possible to include your Instagram feed in your project, using Instagram's Basic Display API. The package stores the feed in your cache, and provides you with the ability to refresh your feeds on whatever schedule suits you.

**Note** This package requires PHP 8. If that is not your cup of tea, then you may continue to use [v2](https://github.com/Dymantic/laravel-instagram-feed/tree/v2.6.0).

### Installation

```
composer require dymantic/laravel-instagram-feed
```

**Note** If you are upgrading from v2.\*, refer to the [upgrade guide](upgrade.md), as there are breaking changes.
**Also Note** This version requires PHP 8 and up, so if you are still on PHP 7 and don't specify a version when you install, composer will pull in ^v2, in which case you should be reading [this page](https://github.com/Dymantic/laravel-instagram-feed/tree/v2.6.0).

## Tutorial

Some parts of this whole thing can be a bit confusing, especially if you are not familiar with following the OAuth flow. I have included [a tutorial](tutorial.md) to try help make things a bit more clear. Feedback or improvements on this would be most appreciated.

### Before you start

To use the Instagram Basic Display API, you will need to have a Facebook app set up with the correct permissions, etc. If you don't have this yet, head over to [the Facebook developer docs](https://developers.facebook.com/docs/instagram-basic-display-api/getting-started) and follow the instructions.

### How Instagram Media is Handled

Instagram provides three media types through this API: image, video and carousel. This package simplifies that into just a feed of images and videos. You may use the `ignore_video` config option if you don't want to include any videos. For carousel items, the first item of the carousel is used. If video is to be ignored, and the first image will be used, if it exists. Carousel items have a `children` property, which includes the actual carousel items.

##### Note on ignoring video

If you chose to ignore video, your feed size may be smaller than the limit you requested. If you expect to be ignoring video posts you may want to increase how many posts you fetch (see "Getting the Feed" below).

### Setup

`php artisan vendor:publish` to publish the necessary migrations and config, and `php artisan migrate` to run migrations.

### Config

Publishing the vendor assets as described above will give you a `config/instagram-feed.php` file. You will need to add your config before you can get started. The comments in the file explain each of the settings.

```php
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
     * The base url used to generate to auth callback route for instagram.
     * This defaults to your APP_URL, so normally you may leave it as null
     */
    'base_url' => null,

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

This package provides a `Dymantic\InstagramFeed\Profile` model which corresponds to an Instagram profile/account. You will need to create a profile for each feed you intend to use in your app/site. Once a profile has been created, you will need to authorize the profile before you can fetch its feed.

#### Creating profiles

You may create profiles programmatically in your code as follows. All you need is to provide a unique username for the profile. This **does not** have to match an Instagram username, it can be any name you wish to use to refer to the profile.

```php
$profile = \Dymantic\InstagramFeed\Profile::new('my profile');
```

Having just a single profile for a project is a fairly common use case, so this package includes an artisan command to quickly create a profile, so that you don't need to build out the necessary UI for your users to do so. Running `php artisan instagram-feed:profile {username}` will create a profile with that username, that you may then use as desired.

### Getting Authorized

Once you have a profile, you may call the `getInstagramAuthUrl()` method on it to get a link to present to the user that will give authentication. When the user visits that url they can then grant access to your app (or not). If everything goes smoothly, the user will be redirected back to the `success_redirect_to` route you set in your config file. If access is not granted, you will be redirected to the `failure_redirect_to` route you configured.

If you have not set your client_id and/or client_secret correctly, or your Instagram app does not accept the user, Instagram won't redirect at all, and your user will see an error from Instagram.

#### Ignoring the default auth callback route

In some cases you may need to ignore the auth callback route from your config so that you may handle that route yourself, To do this you may call `Instgram::ignoreRoutes()` from within your `AppServiceProvider`,

#### Refreshing access tokens

The long lived access tokens for the API expire after 60 days. This package includes an artisan command that will handle this for you, you just need to ensure that it runs at least once every 60 days. The command is `php artisan instagram-feed:refresh-tokens`

### Getting the feed

Once your profile has been authenticated, you can retrieve the feed either directly from the `InstagramFeed` class, or by first getting the profile and calling the feed method on it.

```php
$feed = \Dymantic\InstagramFeed\InstagramFeed::for('my profile');
```

or

```php
$profile = \Dymantic\InstagramFeed\Profile::for('my profile')
$feed = $profile?->feed();
```

Once the feed is fetched, it will be cached forever and the same cached results will be returned until you `refresh` the feed. Normally this is done is a scheduled chron job.

##### Limiting the number of items in the feed

By default, the number of items in the feed is 20. You may pass an optional integer parameter when getting your feed, as such: `\Dymantic\InstagramFeed\InstagramFeed::for('my profile', 15)` or `$profile?->feed(15)` to limit the feed to that amount.

##### Fetching all posts (no limit)

If you want to fetch all your posts (up to the last 1000), you may pass `null` as the limit parameter to your `refreshFeed` or `feed` method. The package will make multiple requests to fetch all your previous posts up to 1000. You may then use the timestamp on the posts to sort, paginate, etc.

##### Setting limits, and cache

When the feed is being supplied from the cache, the limit will have no affect. If you would like to change the limit to what you previously used, you will have to `refresh` the feed.

##### Setting limits and ignore_video

If you chose to ignore video, your feed size may be smaller than the limit you requested. If you expect to be ignoring video posts you may want to increase how many posts you fetch.

### Updating the feed

You may refresh the feed similar to how you fetch the reed:

```php
$feed = \Dymantic\InstagramFeed\InstagramFeed::for('my profile')->refresh();
```

or

```php
$profile = \Dymantic\InstagramFeed\Profile::for('my profile')
$feed = $profile?->refreshFeed();
```

Refreshing the feed returns an instance of `InstagramFeed`, however, unlike getting the normal feed, this method can throw an exception if an error occurs. The idea is that you refresh your feeds in the background somewhere, usually in a scheduled job. This package includes an artisan command `php artisan instagram-feed:refresh`, that will refresh all authorised profiles, and handle errors if they occur. If you have an email address set in the config, that address will be notified in the case of an error. It is recommended to use Laravel's scheduling features to run this command as frequently as you see fit.

## Using the feed

Once you have a feed, you may send it to your view, where it may be iterated over, with each item in the feed being an instance of the `InstagramMedia` class.

```php
// somewhere in a Controller
public function show() {
    $feed = \Dymantic\InstagramFeed\InstagramFeed::for('my profile');

    return view('instagram-feed', ['instagram_feed' => $feed]);
}

// instagram-feed.blade.php

@foreach($instagram_feed as $post)
    <img src={{ $post->url }} alt="A post from my instagram">
@endforeach
```

If you would like more control over the feed's items, you may call the `collect` method on the feed to retrieve the feed items as a Laravel Collection.

#### Feed items

Each feed item is an instance of the `InstagramMedia` class and provides the following properties and methods:

| property      | type   | value                                                                                                                           |
| ------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------- |
| type          | string | either "image", "video" or "carousel"                                                                                           |
| url           | string | the url for the image or video                                                                                                  |
| id            | string | the Instagram id for the image or video                                                                                         |
| caption       | string | caption for the post                                                                                                            |
| permalink     | string | permalink to post on instagram.com                                                                                              |
| thumbnail_url | string | the url for the video thumbnail. Only on video items                                                                            |
| timestamp     | string | the timestamp for the post                                                                                                      |
| children      | array  | For Carousel type posts. Each item will contain only `id`, `url` and `type` fields. Will be empty array for non-carousel posts. |

| method       | return | notes                          |
| ------------ | ------ | ------------------------------ |
| isImage()    | bool   | is the post of "image" type    |
| isVideo()    | bool   | is the post of "video" type    |
| isCarousel() | bool   | is the post of "carousel" type |
| toArray()    | array  | get the item in array form     |
