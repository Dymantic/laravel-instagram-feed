<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

class SimpleClient
{

    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function get($url)
    {
        $response = $this->client->get($url);

        return \GuzzleHttp\json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
    }

    public function post($url, $options)
    {
        $response = $this->client->post($url, [RequestOptions::FORM_PARAMS => $options]);

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }
}