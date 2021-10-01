<?php

namespace Dymantic\InstagramFeed\Tests\Client;

use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\Exceptions\HttpException;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;

class SimpleClientTest extends TestCase
{
    /**
     *@test
     */
    public function the_client_expects_post_response_bodies_to_be_json_and_returns_parsed_json()
    {
        $expected_response_body = [
            "test" => "success"
        ];

        Http::fake([
            'test.test' => Http::response(['test' => 'success'], 200)
        ]);

        $response = SimpleClient::post('https://test.test', ['foo' => 'bar', 'baz' => 'test']);

        $this->assertEquals($expected_response_body, $response);
    }

    /**
     *@test
     */
    public function the_client_expect_get_responses_to_be_in_json_and_returns_parsed_json()
    {
        $expected_response_body = [
            "test" => "success"
        ];
        Http::fake([
            'test.test' => Http::response(['test' => 'success'], 200)
        ]);

        app()->bind(SimpleClient::class, function() {
            return new SimpleClient();
        });

        $client = app()->make(SimpleClient::class);

        $response = SimpleClient::get('https://test.test');

        $this->assertEquals($expected_response_body, $response);
    }

    /**
     *@test
     */
    public function it_throws_an_exception_if_get_request_is_not_a_success()
    {
        Http::fake([
            'test.test' => Http::response(['error_message' => 'bad test request'], 400)
        ]);

        app()->bind(SimpleClient::class, function() {
            return new SimpleClient();
        });

        $client = app()->make(SimpleClient::class);

        $expected_message = "Http request to https://test.test failed with a status of 400 and error message: bad test request";

        try {
            $response = SimpleClient::get('https://test.test');
            $this->fail('expected exception to be thrown');
        } catch (\Exception $e){
            $this->assertInstanceOf(HttpException::class, $e);
            $this->assertSame($expected_message, $e->getMessage());
        }


    }

    /**
     *@test
     */
    public function it_throws_an_exception_if_post_request_is_not_a_success()
    {
        Http::fake([
            'test.test' => Http::response(['error_message' => 'bad test request'], 500)
        ]);

        $expected_message = "Http request to https://test.test failed with a status of 500 and error message: bad test request";

        try {
            $response = SimpleClient::post('https://test.test', []);
            $this->fail('expected exception to be thrown');
        } catch (\Exception $e){
            $this->assertInstanceOf(HttpException::class, $e);
            $this->assertSame($expected_message, $e->getMessage());
        }

    }


}