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
            $user_details = $instagram->fetchUserDetails($token_details);
            $token = $instagram->exchangeToken($token_details);
        } catch (\Exception $e) {
            throw new AccessTokenRequestException($e->getMessage());
        }

        return $this->setToken(array_merge(['access_token' => $token['access_token']], $user_details));
    }

    public function refreshToken()
    {
        $instagram = app()->make(Instagram::class);
        $token = $this->accessToken();
        $new_token = $instagram->refreshToken($token);
        $this->latestToken()->update(['access_code' => $new_token['access_token']]);
    }

    protected function setToken($token_details)
    {
        $this->tokens->each->delete();

        return AccessToken::createFromResponseArray($this, $token_details);
    }

    public function hasInstagramAccess()
    {
        return !! $this->latestToken();
    }

    public function latestToken()
    {
        return $this->tokens()->latest()->first();
    }

    public function accessToken()
    {
        return $this->latestToken()->access_code ?? null;
    }

    public function clearToken()
    {
        $this->tokens->each->delete();
    }

    public function feed($limit = 20)
    {
        if(!$this->latestToken()) {
            return collect([]);
        }
        if (Cache::has($this->cacheKey())) {
            return collect(Cache::get($this->cacheKey()));
        }

        $instagram = app()->make(Instagram::class);

        try {
            $feed = $instagram->fetchMedia($this->latestToken(), $limit);
            Cache::forever($this->cacheKey(), $feed);

            return collect($feed);
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    public function refreshFeed($limit = 20)
    {
        $instagram = app()->make(Instagram::class);
        $new_feed = $instagram->fetchMedia($this->latestToken(), $limit);

        Cache::forget($this->cacheKey());
        Cache::forever($this->cacheKey(), $new_feed);

        return $this->feed();
    }

    public function viewData()
    {
        $token = $this->tokens->first();
        return [
            'name'         => $this->username,
            'username'     => $token->username ?? '',
            'fullname'     => $token->user_fullname ?? '',
            'avatar'       => $token->user_profile_picture ?? '',
            'has_auth'     => $this->hasInstagramAccess(),
            'get_auth_url' => $this->getInstagramAuthUrl()
        ];
    }
}