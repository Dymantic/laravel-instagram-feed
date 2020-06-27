<?php

namespace Dymantic\InstagramFeed\Mail;

use Illuminate\Mail\Mailable;

class FeedRefreshFailed extends Mailable
{
    public $profile;
    public $error_message;

    public function __construct($profile, $error_message = '')
    {
        $this->profile = $profile;
        $this->error_message = $error_message;
    }

    public function build()
    {
        return $this->subject('Unable to refresh IG feed for ' . $this->profile->username)
                    ->markdown('instagram-feed::emails.feed-refresh-failed', [
                        'has_auth' => $this->profile->fresh()->hasInstagramAccess(),
                        'error_message' => $this->error_message,
                    ]);
    }
}