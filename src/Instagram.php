<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use GuzzleHttp\Exception\ClientException;

class Instagram
{
    const REQUEST_ACCESS_TOKEN_URL = "https://api.instagram.com/oauth/access_token";
    const REQUEST_MEDIA_URL = "https://api.instagram.com/v1/users/self/media/recent/?access_token=";

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

        return "https://api.instagram.com/oauth/authorize/?client_id=$client_id&redirect_uri=$redirect&response_type=code";
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

    public function fetchMedia($access_code)
    {
        $url = static::REQUEST_MEDIA_URL . $access_code;

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

        return collect($response['data'])->map(function ($media) {
            return [
                'low'      => $media['images']['low_resolution']['url'] ?? '',
                'thumb'    => $media['images']['thumbnail']['url'] ?? '',
                'standard' => $media['images']['standard_resolution']['url'] ?? '',
                'likes'    => $media['likes']['count'] ?? '',
                'caption'  => $media['caption']['text'] ?? ''
            ];
        })->reject(function ($media) {
            return empty($media['thumb']);
        })->all();
    }


}