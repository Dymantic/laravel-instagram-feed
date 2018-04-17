<?php


namespace Dymantic\InstagramFeed;


class SimpleClient
{

    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function post($url, $options)
    {
        $response = $this->client->post($url, ['body' => $options]);

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }
}