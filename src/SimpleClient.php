<?php


namespace Dymantic\InstagramFeed;


use GuzzleHttp\RequestOptions;

class SimpleClient
{

    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function post($url, $options)
    {
        $response = $this->client->post($url, [RequestOptions::FORM_PARAMS => $options]);

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }
}