<?php


namespace Dymantic\InstagramFeed;


class MediaParser
{
    public static function parseItem($media, $ignore_video = false)
    {
        $type = $media['media_type'];

        switch ($type) {
            case 'IMAGE':
                return static::parseAsImage($media);
                break;
            case 'VIDEO':
                return static::parseAsVideo($media, $ignore_video);
                break;
            case 'CAROUSEL_ALBUM':
                return static::parseAsCarousel($media, $ignore_video);
                break;
        }
    }

    private static function parseAsImage($media)
    {
        return [
            'type' => 'image',
            'url' => $media['media_url'],
            'id' => $media['id'],
            'caption' => (array_key_exists('caption', $media) ? $media['caption'] : null),
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'] ?? ''
        ];

    }

    private static function parseAsVideo($media, $ignore_video)
    {
        if ($ignore_video) {
            return;
        }

        return [
            'type' => 'video',
            'url' => $media['media_url'],
            'id' => $media['id'],
            'caption' => (array_key_exists('caption', $media) ? $media['caption'] : null),
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'] ?? ''
        ];
    }

    private static function parseAsCarousel($media, $ignore_video)
    {
        $use = collect($media['children']['data'])
            ->first(function ($child) use ($ignore_video) {
                return $child['media_type'] === 'IMAGE' || (!$ignore_video);
            });

        return [
            'type' => strtolower($use['media_type']),
            'url' => $use['media_url'],
            'id' => $media['id'],
            'caption' => (array_key_exists('caption', $media) ? $media['caption'] : null),
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'] ?? ''
        ];
    }

    private static function firstCarouselItem($media, $ignore_video)
    {
        return collect($media['carousel_media'])
            ->first(function ($item) use ($ignore_video) {
                return !$ignore_video || ($item["images"] ?? false);
            });
    }

    private static function extractVideo($media)
    {
        return [
            'type' => 'video',
            'low' => $media['low_bandwidth']['url'] ?? null,
            'thumb' => $media['low_resolution']['url'] ?? null,
            'standard' => $media['standard_resolution']['url'] ?? null,
        ];
    }

    private static function extractImage($media)
    {
        return [
            'type' => 'image',
            'low' => $media['low_resolution']['url'] ?? null,
            'thumb' => $media['thumbnail']['url'] ?? null,
            'standard' => $media['standard_resolution']['url'] ?? null,
        ];
    }
}
