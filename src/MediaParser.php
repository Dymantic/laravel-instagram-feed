<?php


namespace Dymantic\InstagramFeed;


class MediaParser
{
    public static function parseItem($media, $ignore_video = false)
    {

        dump($media);

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
            'thumbnail_url' => $media['thumbnail_url'] ?? '',
            'timestamp' => $media['timestamp'] ?? ''
        ];
    }

    private static function parseAsCarousel($media, $ignore_video)
    {

        $use = collect($media['children']['data'])
            ->first(function ($child) use ($ignore_video) {
                return $child['media_type'] === 'IMAGE' || (!$ignore_video);
            });
        
        if (!$use) {
            return;
        }

        return [
            'type' => strtolower($use['media_type']),
            'url' => $use['media_url'],
            'id' => $media['id'],
            'caption' => (array_key_exists('caption', $media) ? $media['caption'] : null),
            'permalink' => $media['permalink'],
            'thumbnail_url' => $use['thumbnail_url'] ?? '',
            'timestamp' => $media['timestamp'] ?? ''
        ];
    }


}
