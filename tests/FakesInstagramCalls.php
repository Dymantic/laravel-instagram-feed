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
            'access_code'          => 'VALID_ACCESS_TOKEN',
            'username'             => 'TEST USERNAME',
            'user_id'              => 'TEST USER_ID',
            'user_fullname'        => 'TEST_USER_FULLNAME',
            'user_profile_picture' => 'https://test.test/avatar'
        ];
    }
}