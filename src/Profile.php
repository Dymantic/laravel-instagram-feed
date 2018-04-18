<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\AccessTokenRequestException;
use Dymantic\InstagramFeed\Exceptions\RequestTokenException;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'dymantic_instagram_basic_profiles';

    protected $guarded = [];

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
}