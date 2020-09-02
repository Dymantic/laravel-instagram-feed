# Using this package to show your Instagram feed on your site

There are four different things you need to do, or know about, to use this package as you want. They are:

1. Generating profiles (a profile represents an Instagram user)
2. Getting permission from the Instagram user for your app to access their feed. This is the most annoying and potentially confusing part.
3. Fetching the actual feed from Instagram.
4. Displaying the feed on your website.

### Profiles

Profiles are there to represent Instagram users. Your database will need to store access tokens, and these profiles provide a place for that. All you need to care about is giving a profile a username that you can use to get the profile again when you need.

There are two ways to create a profile. Either directly in your code `$profile = \Dymantic\InstagramFeed\Profile::create(['username' => 'michael'])` or you can use an artisan command `php artisan instagram-feed:profile michael`.

Then when you need to fetch the profile, you can use Eloquent as for any other model, for example `$profile = \Dymantic\InstagramFeed\Profile::where('username', 'michael')->first()`.

### Getting auth (The OAuth flow)

For this part you need to make sure your config in `config/instagram-feed.php` has been completed, and you need to set up some routes. The simplest way we can do this is in three steps:

First, we need a route to a page where we will present a link for our Instagram user (most likely ourselves) to click and begin the auth process. You probably don't want the general public to access this page or route, only you/the Instagram user. Once the user clicks on the link, they will be taken to an Instagram page where they will need to agree to give permission for your app/site to use their feed.

```
//routes file
// You can leave off the ->middleware('auth') part for now if you don't want to bother with restricting access for now
Route::get('instagram-get-auth', 'InstgramAuthController@show')->middleware('auth');

//in InsatgramAuthController.php
public function show() {
    $profile = \Dymantic\InstagramFeed\Profile::where('username', 'michael')->first();

    return view('instagram-auth-page', ['instagram_auth_url' => $profile->getInstagramAuthUrl()]);
}

//somewhere in instagram-auth-page.blade.php
<a href="{{ $instagram_auth_url }}">Click to get Instgram permission</a>
```

You should now be able to visit that route in your browser and see the link. Do that and click on it.

Next, we need a route that Instagram will redirect the user to once they have given permission. This is what matches the redirect url in your Facebook app settings, and the `auth_callback_route` in your config file. You don't need to make a controller or view for this route.

```
//in your Facebook app settings
Valid OAuth Redirect URIs: https://example.test/instagram/auth/callback

Then your config should have the route that matches everything after the forward slash following your app_url (https://example.test/ in this case).

Note that you can use more than one url in your Facebook settings, so you can add a url for local dev as well.

//in config/instagram-feed.php
'auth_callback_route' => 'instagram/auth/callback',
```

Finally we need a route to handle the response once our app and Instagram finish their OAuth business. This is where we find out if everything was successful or not. We are going to create one route, controller and view to handle both success and failure.

```
//config/instagram-feed.php
// This is slightly different to what we have in default config.
/*
     * On success of the OAuth process you will be redirected to this route.
     * You may use query strings to carry messages
     */
    'success_redirect_to' => 'instagram-auth-response?result=success',

    /*
     * If the OAuth process fails for some reason you will be redirected to this route.
     * You may use query strings to carry messages
     */
    'failure_redirect_to' => 'instagram-auth-response?result=failure'

//routes file
Route::get('instagram-auth-response', 'InstagramAuthController@complete');

//InstagramAuthController.php
public function complete() {
    $was_successful = request('result') === 'success';

    return view('instagram-auth-response-page', ['was_successful' => $was_successful]);
}

//somewhere in instagram-auth-response-page.blade.php
@if($was_successful)
 <p>Yes, we can now use your instagram feed</p>
@else
 <p>Sorry, we failed to get permission to use your insagram feed.</p>
@endif
```

If we followed through with all this successfully, our app now has the ability to fetch the Instagram feed for that profile. While all that was a lot, we don't do it everytime we want to use the profiles feed, it is just to get permission and to get set up. The rest is much more straightforward.

### Fetching the feed for a profile

Now that we have permission and access, we can start to fetch the feed for our profile. Because we may display our INstagram feed more often than we actually post to INstagram, we don't want to always make a network request to Instagram everytime we want to display the feed on our site. So normally we are using a cached feed, and we should update that when we need, such as once a day. So our profile has two methods, `feed` and `refreshFeed`. Calling `feed` will only make a network request to update the feed if there is nothing in the cache. Calling `refreshFeed` will always fetch the fresh feed from Instagram. I recommend you use Laravel's [scheduling features](https://laravel.com/docs/7.x/scheduling) to run the artisan command `instagram-feed:refresh` once a day, or how ever often you feel is neccessary.

### Displaying the feed on your site

Then, to finally show the feed, in the controller method for the view you are showing the feed you need to do something like the following (let's assume the feed will be on our home page):

```
//HomePageController.php
public function show() {
    $feed = \Dymantic\InstagramFeed\Profile::where('username', 'michael')->first()->feed();

    return view('home-page', ['instagram' => $feed]);
}

//somewhere in the blade file for you home page
@foreach($instagram as $post)
    <img src="{{ $post['url']}}">
@endforeach
```

### Keeping authenticated

In the OAuth flow you went through before, Instagram provides a token for your app to use to access the feed. This token expires in 60 days, and if you don't renew it, you will have to go through the whole auth process again. This package provides an artisan command `instagram-feed:refresh-tokens` for you to run to fresh your token. I recommend you use Laravel's scheduling to do that every 59 days or so.
