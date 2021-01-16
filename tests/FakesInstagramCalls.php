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
            'access_token' => 'VALID_ACCESS_TOKEN',
            'user_id'         => 'FAKE_USER_ID',
        ];
    }

    private function validLongLivedToken()
    {
        return [
            'access_token' => 'VALID_LONG_LIVED_TOKEN',
            'token_type' => 'bearer',
            'expires_in' => 1234567,
        ];
    }

    private function refreshedLongLivedToken()
    {
        return [
            'access_token' => 'REFRESHED_LONG_LIVED_TOKEN',
            'token_type' => 'bearer',
            'expires_in' => 1234567,
        ];
    }

    private function validUserDetails()
    {
        return [
            'id' => 'FAKE_USER_ID',
            'username' => 'instagram_test_username',
        ];
    }

    private function validUserWithToken()
    {
        return [
            'access_token' => 'VALID_LONG_LIVED_TOKEN',
            'id' => 'FAKE_USER_ID',
            'username' => 'instagram_test_username',
            'name' => 'test user real name',
            'profile_picture_url' => 'https://test.test/test_pic.jpg',
        ];
    }

    private function exampleMediaResponse($with_next_page = false)
    {
        return $with_next_page ?
        json_decode(file_get_contents("./tests/basic_display_media_response_200.json"), true) :
        json_decode(file_get_contents("./tests/basic_display_media_response_200_no_next_page.json"), true);
    }


}