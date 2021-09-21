<?php


namespace Dymantic\InstagramFeed;


use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Exceptions\HttpException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;

class SimpleClient
{

    public static function get($url)
    {
        $response = Http::accept('application/json')->get($url);

        if($response->failed()) {
            $message = $response->json('error_message', 'unknown error');
            throw HttpException::new($url, $response->status(), $message, $response->json());
        }

        return $response->json();
    }

    public static function post($url, $options)
    {
        $response = Http::accept('application/json')->asForm()->post($url, $options);

        if($response->failed()) {
            $message = $response->json('error_message', 'unknown error');
            throw HttpException::new($url, $response->status(), $message, $response->json());
        }

        return $response->json();
    }
}