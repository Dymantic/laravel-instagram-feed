<?php


namespace Dymantic\InstagramFeed;


use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $guarded = [];

    protected $table = 'dymantic_instagram_feed_tokens';

    public static function createFromResponseArray($profile, $token_details)
    {
        return static::create([
            'profile_id' => $profile->id,
            'access_code' => $token_details['access_token'],
            'user_id' => $token_details['user']['id'],
            'username' => $token_details['user']['username'],
            'user_fullname' => $token_details['user']['full_name'],
            'user_profile_picture' => $token_details['user']['profile_picture']
        ]);
    }
}