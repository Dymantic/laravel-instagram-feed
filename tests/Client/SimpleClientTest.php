<?php

namespace Dymantic\InstagramFeed\Tests\Client;

use Dymantic\InstagramFeed\SimpleClient;
use Dymantic\InstagramFeed\Tests\MockableDummyHttpClient;
use Dymantic\InstagramFeed\Tests\TestCase;

class SimpleClientTest extends TestCase
{
    /**
     *@test
     */
    public function the_client_expects_request_response_bodies_to_be_json_and_returns_parsed_json()
    {
        $expected_response_body = [
            "test" => "success"
        ];
        $mockHttp = $this->createMock(MockableDummyHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with($this->equalTo('https://test.test'), $this->equalTo([
                'body' => [
                    'foo' => 'bar',
                    'baz' => 'test'
                ]
            ]))
            ->willReturn($this->mockResponse());

        app()->bind(SimpleClient::class, function() use ($mockHttp) {
            return new SimpleClient($mockHttp);
        });

        $client = app()->make(SimpleClient::class);

        $response = $client->post('https://test.test', ['foo' => 'bar', 'baz' => 'test']);

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