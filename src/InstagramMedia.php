<?php

namespace Dymantic\InstagramFeed;

class InstagramMedia
{

    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_CAROUSEL = 'carousel';
    const TYPE_UNKNOWN = 'unknown';

    public string $type;
    public string $url;
    public string $id;
    public string $permalink;
    public string $caption;
    public string $timestamp;
    public string $thumbnail_url;
    public array $children;

    public static function newImage($attributes): self
    {
        return new self(self::TYPE_IMAGE, $attributes);
    }

    public static function newVideo(array $attributes): self
    {
        return new self(self::TYPE_VIDEO, $attributes);
    }

    public static function newCarousel(array $attributes): self
    {
        return new self(self::TYPE_CAROUSEL, $attributes);
    }

    public function __construct(string $type, array $attributes)
    {
        $this->type = $type;
        $this->url = $attributes['url'] ?? '';
        $this->id = $attributes['id'] ?? '';
        $this->caption = $attributes['caption'] ?? '';
        $this->permalink = $attributes['permalink'] ?? '';
        $this->thumbnail_url = $attributes['thumbnail_url'] ?? '';
        $this->timestamp = $attributes['timestamp'] ?? '';
        $this->children = $attributes['children'] ?? [];
    }

    public function isImage()
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function isCarousel(): bool
    {
        return $this->type === self::TYPE_CAROUSEL;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
            'id' => $this->id,
            'caption' => $this->caption,
            'permalink' => $this->permalink,
            'thumbnail_url' => $this->thumbnail_url,
            'timestamp' => $this->timestamp,
            'is_carousel' => $this->isCarousel(),
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'children' => $this->children,
        ];
    }
}