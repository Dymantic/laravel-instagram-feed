<?php

namespace Dymantic\InstagramFeed;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;

class InstagramFeed implements IteratorAggregate, Countable
{

    public function __construct(public ?Profile $profile, private array $items)
    {}

    public static function for(string|Profile $profile, int $limit = 20): InstagramFeed
    {
        if(is_string($profile)) {
            $profile = Profile::for($profile);
        }

        return new $profile ? $profile->feed($limit) : self::empty();
    }

    public static function empty(): InstagramFeed
    {
        return new self(null, []);
    }

    public function collect(): Collection
    {
        return collect($this->items);
    }

    public function refresh(int $limit = 20): void
    {
        if($this->profile) {
            $this->items = $this->profile->refreshFeed($limit)->collect()->all();
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }
}