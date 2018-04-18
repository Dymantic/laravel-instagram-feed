<?php


namespace Dymantic\InstagramFeed\Tests;


trait FakesInstagramCalls
{
    private function successAuthRequest()
    {
        return new class
        {
            public function get($reqParameter)
            {
                if ($reqParameter === 'code') {
                    return 'TEST_REQUEST_CODE';
                }
            }

            public function has($reqParameter)
            {
                if ($reqParameter === 'error') {
                    return false;
                }

                if ($reqParameter === 'code') {
                    return true;
                }
            }
        };
    }

    private function deniedAuthRequest()
    {
        return new class
        {
            public function get($reqParameter)
            {
                if ($reqParameter === 'error') {
                    return 'access_denied';
                }
            }

            public function has($reqParameter)
            {
                return $reqParameter === 'error';
            }
        };
    }

    private function validTokenDetails()
    {
        return [
            'access_token' => 'TEST_TOKEN_CODE',
            'user' => [
                'id' => 'TEST ID',
                'username' => 'TEST_USERNAME',
                'full_name' => 'TEST FULL NAME',
                'profile_picture' => 'TEST AVATAR'
            ]
        ];
    }
}