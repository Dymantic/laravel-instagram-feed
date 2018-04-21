# Laravel Instagram Feed

## Easily include your Instagram feed(s) in your project.

The aim of this package is to make it as simple and user-friendly as possible to include your Instagram feed in your project. The package is made so that ideally the view is almost always just using cached data, and the feed itself will be updated at a schedule of your choosing, using Laravel's great scheduling features. The feed is also designed to be resilient, so that you can safely call it from your controllers without having to worry about errors breaking the page.

### Installation

```
composer require dymantic/laravel-instagram-feed
```

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
];
```

### Profiles

All Instagram api calls now need auth via OAuth, and so you need an entity to associate the resulting access token with. This package provides a `Dymantic\InstagramFeed\Profile` model to fit that role. An instance of the model requires a username, so you have some way to refer to it, and it is through this model that you will access the Instagram feed belomging to the access token granted to the profile. You may have several profiles, which means you may have more than one Instagram feed. How you use the Profiles is up to you (e.i. associating with users, or just having one profile, etc).

Having just a single profile for a project is a fairly common use case, so this package includes an artisan command to quickly create a profile, so that you don't need to build out the necessary UI for your users to do so. Running `php artisan instagram-feed:profile {username}` will create a profile with that username, that you may then use as desired.

### Getting Authorized

Once you have a profile, you may call the `getInstagramAuthUrl()` method on it to get a link to present to the user that will give authentication. When the user visits that url they can then grant access to your app (or not). If everything goes smoothly, the user will be redirected back to the route you configured. If access is not granted, you will be redirected to the alternate route you configured. If you have not set your client_id and/or client_secret correctly, or your Instagram app does not accept the user (because you are in Sandbox mode), Instagram won't redirect at all, and your user will see an error page from Instagram.

### Getting the feed

Once you have an authenticated profile, you may call the `feed()` method on that profile. The first time the method is called the feed will be fetched from Instagram and it will the be cached forever. Consequent calls to the `feed()` method will simply fetch from the cache. The `feed()` method can be safely called without worrying about exceptions and errors, if something goes wrong, you will just receive an empty collection.

The feed will be a Laravel collection of items that have the following structure:

```
[
    'low' => 'url for low resolution image',
    'thumb' => 'url for thumbnail image',
    'standard' => 'url for standard resolution of image',
    'likes' => 'The number of likes as integer',
    'caption' => 'The caption text of the Instagram post'
]
```

### Updating the feed

Obviously the feed needs to be updated, which is exactly what the `refreshFeed()` method on a Profile instance does. This method will return the same kind of collection as the `feed()` method if successful. However, this method will throw an Exception if one happens to occur (network failure, invalid token, etc).

This package includes an artisan command `php artisan instagram-feed:refresh`, that will refresh all authorised profiles, and handle errors if they occur. If you have an email address set in the config, that address will be notified in the case of an error. It is recommended to use Laravel's scheduling features to run this command as frequently as you see fit.

