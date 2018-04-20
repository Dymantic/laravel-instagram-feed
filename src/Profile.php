<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\AccessTokenRequestException;
use Dymantic\InstagramFeed\Exceptions\RequestTokenException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Profile extends Model
{
    const CACHE_KEY_BASE = 'dymantic_instagram_feed';
    protected $table = 'dymantic_instagram_basic_profiles';

    protected $guarded = [];

    public function cacheKey()
    {
        return static::CACHE_KEY_BASE . ":" . $this->id;
    }

    public function getInstagramAuthUrl()
    {
        $instagram = app()->make(Instagram::class);

        return $instagram->authUrlForProfile($this);
    }

    public function tokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function requestToken($request)
    {
        if ($request->has('error') || !$request->has('code')) {
            throw new RequestTokenException('Unable to get request token');
        }

        $instagram = app()->make(Instagram::class);

        try {
            $token_details = $instagram->requestTokenForProfile($this, $request);
        } catch(\Exception $e) {
            throw new AccessTokenRequestException($e->getMessage());
        }

        return $this->setToken($token_details);
    }

    protected function setToken($token_details)
    {
        $this->tokens->each->delete();

       return AccessToken::createFromResponseArray($this, $token_details);
    }

    public function hasInstagramAccess()
    {
        return $this->tokens()->count() > 0;
    }

    public function accessToken()
    {
        $token = $this->tokens()->first();
        return $token ? $token->access_code : null;
    }

    public function clearToken() {
        $this->tokens->each->delete();
    }

    public function feed()
    {
        if(Cache::has($this->cacheKey())) {
            return Cache::get($this->cacheKey());
        }

        $instagram = app()->make(Instagram::class);

        try {
            $feed = $instagram->fetchMedia($this->accessToken());
            Cache::forever($this->cacheKey(), $feed);
            return $feed;
        } catch(\Exception $e) {
            return [];
        }
    }

    public function refreshFeed()
    {
        $instagram = app()->make(Instagram::class);
        $new_feed = $instagram->fetchMedia($this->accessToken());

        Cache::forget($this->cacheKey());
        Cache::forever($this->cacheKey(), $new_feed);

        return $this->feed();
    }
}