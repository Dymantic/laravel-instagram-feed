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

            case 'VIDEO':
                return static::parseAsVideo($media, $ignore_video);

            case 'CAROUSEL_ALBUM':
                return static::parseAsCarousel($media, $ignore_video);

            default:
                return null;
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
            'thumbnail_url' => $media['media_url'],
            'timestamp' => $media['timestamp'] ?? '',
            'is_carousel' => false,
            'children' => [],
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
            'thumbnail_url' => $media['thumbnail_url'] ?? '',
            'timestamp' => $media['timestamp'] ?? '',
            'is_carousel' => false,
            'children' => [],

        ];
    }

    private static function parseAsCarousel($media, $ignore_video)
    {

        $children = collect($media['children']['data'])
             ->filter(function ($child) use ($ignore_video) {
                 return $child['media_type'] === 'IMAGE' || (!$ignore_video);
             });

        if (!$children) {
            return;
        }

        $use = $children->first();

        return [
            'type' => strtolower($use['media_type']),
            'url' => $use['media_url'],
            'id' => $media['id'],
            'caption' => (array_key_exists('caption', $media) ? $media['caption'] : null),
            'permalink' => $media['permalink'],
            'thumbnail_url' => $use['thumbnail_url'] ?? '',
            'timestamp' => $media['timestamp'] ?? '',
            'is_carousel' => $children->count() > 0,
            'children' => $children->map(function ($child) {
                return [
                   'type' => strtolower($child['media_type']),
                   'url' => $child['media_url'],
                   'id' => $child['id'],         
                ];
            })->values()->all(),
        ];
    }


}
