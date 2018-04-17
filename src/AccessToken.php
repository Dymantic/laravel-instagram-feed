<?php


namespace Dymantic\InstagramFeed;


use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $guarded = [];

    protected $table = 'dymantic_instagram_feed_tokens';
}