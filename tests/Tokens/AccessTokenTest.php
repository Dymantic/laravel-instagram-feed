<?php

namespace Dymantic\InstagramFeed\Tests\Tokens;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\Tests\FakesInstagramCalls;
use Dymantic\InstagramFeed\Tests\TestCase;

class AccessTokenTest extends TestCase
{
    use FakesInstagramCalls;
    /**
     *@test
     */
    public function a_token_can_be_created_from_an_instagram_response_array()
    {
        $profile = Profile::create(['username' => 'test user']);
        $token = AccessToken::createFromResponseArray($profile, $this->validUserWithToken());

        $this->assertEquals('VALID_LONG_LIVED_TOKEN', $token->access_code);
        $this->assertEquals('FAKE_USER_ID', $token->user_id);
        $this->assertEquals('instagram_test_username', $token->username);
        $this->assertEquals('not available', $token->user_fullname);
        $this->assertEquals('not available', $token->user_profile_picture);
    }
}