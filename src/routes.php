<?php

Route::get(config('instagram-feed.auth_callback_route'), 'Dymantic\InstagramFeed\AccessTokenController@handleRedirect');