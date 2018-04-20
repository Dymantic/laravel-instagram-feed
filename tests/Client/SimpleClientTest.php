<?php

namespace Dymantic\InstagramFeed\Tests\Client;

use Dymantic\InstagramFeed\Exceptions\BadTokenException;
use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

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
        $mockHttp = $this->createMock(MockableDummyHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with($this->equalTo('https://test.test'), $this->equalTo([RequestOptions::FORM_PARAMS => [
                    'foo' => 'bar',
                    'baz' => 'test'
            ]]))
            ->willReturn($this->mockResponse());

        app()->bind(SimpleClient::class, function() use ($mockHttp) {
            return new SimpleClient($mockHttp);
        });

        $client = app()->make(SimpleClient::class);

        $response = $client->post('https://test.test', ['foo' => 'bar', 'baz' => 'test']);

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
        $mockHttp = $this->createMock(MockableDummyHttpClient::class);
        $mockHttp->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('https://test.test'))
                 ->willReturn($this->mockResponse());

        app()->bind(SimpleClient::class, function() use ($mockHttp) {
            return new SimpleClient($mockHttp);
        });

        $client = app()->make(SimpleClient::class);

        $response = $client->get('https://test.test');

        $this->assertEquals($expected_response_body, $response);
    }





    private function mockResponse()
    {
        return new class {
            public function getBody()
            {
                return json_encode([
                    "test" => "success"
                ]);
            }
        };
    }


}