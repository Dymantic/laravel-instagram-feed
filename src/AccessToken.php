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
            'user_id' => $token_details['id'],
            'username' => $token_details['username'],
            'user_fullname' => 'not available',
            'user_profile_picture' => 'not available',
        ]);
    }
}