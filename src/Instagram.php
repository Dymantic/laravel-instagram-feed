<?php


namespace Dymantic\InstagramFeed;


class Instagram
{
    const REQUEST_ACCESS_TOKEN_URL = "https://api.instagram.com/oauth/access_token";
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $http;

    public function __construct($config, $client)
    {
        $this->client_id = $config["client_id"];
        $this->client_secret = $config["client_secret"];
        $this->redirect_uri = $config["redirect_uri"];

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
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUriForProfile($profile->id),
            'code' => $auth_request->get('code')
        ]);
    }

    private function redirectUriForProfile($profile_id)
    {
        return $this->redirect_uri . '?profile=' . $profile_id;
    }


}