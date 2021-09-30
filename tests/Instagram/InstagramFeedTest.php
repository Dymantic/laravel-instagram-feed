<?php

namespace Dymantic\InstagramFeed\Tests\Instagram;

use Dymantic\InstagramFeed\AccessToken;
use Dymantic\InstagramFeed\InstagramFeed;
use Dymantic\InstagramFeed\InstagramMedia;
use Dymantic\InstagramFeed\Profile;
use Dymantic\InstagramFeed\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class InstagramFeedTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_the_feed_for_a_profile()
    {

        $this->setUpTestProfileWithFeed('test user');

        $feed = InstagramFeed::for('test user');

        $this->assertCount(4, $feed);

        foreach ($feed as $item) {
            $this->assertInstanceOf(InstagramMedia::class, $item);
        }
    }

    /**
     *@test
     */
    public function the_profile_is_available_from_the_feed()
    {
        $profile = $this->setUpTestProfileWithFeed('test user');

        $feed = InstagramFeed::for('test user');

        $this->assertTrue($feed->profile->is($profile));
    }

    /**
     *@test
     */
    public function the_required_limit_for_the_feed_can_be_used()
    {
        $this->setUpTestProfileWithFeed('test user');

        $feed = InstagramFeed::for('test user', 2);

        $this->assertCount(2, $feed);
    }


    /**
     *@test
     */
    public function can_use_profile_directly_to_get_feed()
    {
        $profile = $this->setUpTestProfileWithFeed('test user');

        $feed = InstagramFeed::for($profile);

        $this->assertCount(4, $feed);
    }

    /**
     *@test
     */
    public function feed_can_be_refreshed()
    {
        $profile = $this->setUpTestProfileWithFeed('test user');
        cache([$profile->cacheKey() => [
            InstagramMedia::newImage(['id' => 'TEST123'])
        ]]);

        $feed = InstagramFeed::for($profile);
        $this->assertCount(1, $feed);

        $feed->refresh(3);

        $this->assertCount(3, $feed);

        foreach ($feed as $item) {
            $this->assertNotSame('TEST123', $item->id);
        }
    }

    /**
     *@test
     */
    public function the_feed_can_be_accessed_as_a_collection()
    {
        $this->setUpTestProfileWithFeed('test user');

        $feed = InstagramFeed::for('test user');

        $this->assertInstanceOf(Collection::class, $feed->collect());
        $this->assertCount(4, $feed->collect());

        $feed->collect()
            ->each(fn ($item) => $this->assertInstanceOf(InstagramMedia::class, $item));
    }

    private function setUpTestProfileWithFeed(string $username): Profile
    {
        $profile = Profile::create(['username' => $username]);
        AccessToken::createFromResponseArray($profile, [
            'profile_id'           => $profile->id,
            'access_token'         => '123456',
            'id'                   => '123456',
            'username'             => 'test user',
            'user_fullname'        => 'not available',
            'user_profile_picture' => 'not available',
        ]);
        $this->generateFeedFor($profile);

        return $profile;
    }

    private function generateFeedFor(Profile $profile)
    {
        Http::fake([
            'https://graph.instagram.com/*' => Http::response(json_decode(file_get_contents("./tests/basic_display_media_response_200_no_next_page.json"), true)),
        ]);

    }
}