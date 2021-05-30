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
        $response = Http::get($url);

        if($response->failed()) {
            $message = $response->json('error', [])['message'] ?? 'unknown error';
            throw HttpException::new($url, $response->status(), $message, $response->json());
        }

        return $response->json();
    }

    public static function post($url, $options)
    {
        $response = Http::post($url, $options);

        if($response->failed()) {
            $message = $response->json('error', [])['message'] ?? 'unknown error';
            throw HttpException::new($url, $response->status(), $message, $response->json());
        }

        return $response->json();
    }
}