<?php

namespace Dymantic\InstagramFeed\Mail;

use Illuminate\Mail\Mailable;

class FeedRefreshFailed extends Mailable
{
    public $profile;

    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    public function build()
    {
        return $this->subject('Unable to refresh IG feed for ' . $this->profile->username)
                    ->markdown('instagram-feed::emails.feed-refresh-failed', [
                        'has_auth' => $this->profile->fresh()->hasInstagramAccess()
                    ]);
    }
}