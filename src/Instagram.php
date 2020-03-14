<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use GuzzleHttp\Exception\ClientException;

class Instagram
{
    const REQUEST_ACCESS_TOKEN_URL = "https://api.instagram.com/oauth/access_token";
    const GRAPH_USER_INFO_FORMAT = "https://graph.instagram.com/%s?fields=id,username,name,profile_picture_url&access_token=%s";
    const EXCHANGE_TOKEN_FORMAT = "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=%s&access_token=%s";
    const REFRESH_TOKEN_FORMAT = "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=%s";
    const MEDIA_URL_FORMAT = "https://graph.instagram.com/%s/media?fields=%s&limit=%s&access_token=%s";
    const MEDIA_FIELDS = "caption,id,media_type,media_url,thumbnail_url,children.media_type,children.media_url";


    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $http;

    public function __construct($config, $client)
    {
        $this->client_id = $config["client_id"];
        $this->client_secret = $config["client_secret"];
        $this->redirect_uri = $config["auth_callback_route"];

        $this->http = $client;
    }

    public function authUrlForProfile($profile)
    {
        $client_id = $this->client_id;
        $redirect = $this->redirectUriForProfile($profile->id);

        return "https://api.instagram.com/oauth/authorize/?client_id=$client_id&redirect_uri=$redirect&scope=user_profile,user_media&response_type=code";
    }

    public function requestTokenForProfile($profile, $auth_request)
    {
        return $this->http->post(static::REQUEST_ACCESS_TOKEN_URL, [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUriForProfile($profile->id),
            'code'          => $auth_request->get('code')
        ]);
    }

    private function redirectUriForProfile($profile_id)
    {
        $base = rtrim(config('app.url'), '/');

        return "{$base}/{$this->redirect_uri}?profile={$profile_id}";
    }

    public function fetchUserDetails($access_token)
    {
        $url = sprintf(self::GRAPH_USER_INFO_FORMAT, $access_token['user_id'], $access_token['access_token']);
        return $this->http->get($url);
    }

    public function exchangeToken($short_token)
    {
        $url = sprintf(self::EXCHANGE_TOKEN_FORMAT, $this->client_secret, $short_token['access_token']);

        return $this->http->get($url);
    }

    public function refreshToken($token)
    {
        $url = sprintf(self::REFRESH_TOKEN_FORMAT, $token);
        return $this->http->get($url);
    }

    public function fetchMedia(AccessToken $token)
    {
        $limit = 20;
        $url = sprintf(self::MEDIA_URL_FORMAT, $token->user_id, self::MEDIA_FIELDS, $limit, $token->access_code);

        try {
            $response = $this->http->get($url);
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody(), true);
            $error_type = $response['meta']['error_type'] ?? 'unknown';
            if ($error_type === 'OAuthAccessTokenException') {
                throw new BadTokenException('The token is invalid');
            } else {
                throw $e;
            }
        }

        return collect($response['data'])
            ->map(function($media) {
                return MediaParser::parseItem($media, config('instagram-feed.ignore_video', false));
            })
            ->reject(function ($media) {
                return is_null($media);
            })->all();
    }


}