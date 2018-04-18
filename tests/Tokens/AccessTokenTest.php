<?php

namespace Dymantic\InstagramFeed\Tests\Tokens;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\Tests\TestCase;

class AccessTokenTest extends TestCase
{
    /**
     *@test
     */
    public function a_token_can_be_created_from_an_instagram_response_array()
    {
        $instagram_response = [
            'access_token' => 'TEST_TOKEN_CODE',
            'user' => [
                'id' => 'TEST ID',
                'username' => 'TEST_USERNAME',
                'full_name' => 'TEST FULL NAME',
                'profile_picture' => 'TEST AVATAR'
            ]
        ];
        $profile = Profile::create(['username' => 'test user']);

        $token = AccessToken::createFromResponseArray($profile, $instagram_response);

        $this->assertEquals('TEST_TOKEN_CODE', $token->access_code);
        $this->assertEquals('TEST ID', $token->user_id);
        $this->assertEquals('TEST_USERNAME', $token->username);
        $this->assertEquals('TEST FULL NAME', $token->user_fullname);
        $this->assertEquals('TEST AVATAR', $token->user_profile_picture);
    }
}