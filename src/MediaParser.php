<?php


namespace Dymantic\InstagramFeed;


class MediaParser
{
    public static function parseItem($media, $ignore_video = false)
    {
        $type = $media['type'];

        switch ($type) {
            case 'image':
                return static::parseAsImage($media);
                break;
            case 'video':
                return static::parseAsVideo($media, $ignore_video);
                break;
            case 'carousel':
                return static::parseAsCarousel($media, $ignore_video);
                break;
        }
    }

    private static function parseAsImage($media)
    {
        $parsed = static::extractImage($media['images']);
        $parsed['likes'] = $media['likes']['count'] ?? null;

        return $parsed;
    }

    private static function parseAsVideo($media, $ignore_video)
    {
        if ($ignore_video) {
            return;
        }

        $parsed = static::extractVideo($media['videos']);
        $parsed['likes'] = $media['likes']['count'] ?? null;

        return $parsed;
    }

    private static function parseAsCarousel($media, $ignore_video)
    {
        $first_item = static::firstCarouselItem($media, $ignore_video);

        if (!$first_item) {
            return;
        }

        if ($first_item['videos'] ?? false) {
            $parsed = static::extractVideo($first_item['videos']);
        }

        if ($first_item['images'] ?? false) {
            $parsed = static::extractImage($first_item['images']);
        }

        $parsed['likes'] = $media['likes']['count'] ?? null;

        return $parsed;
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
            'type'     => 'video',
            'low'      => $media['low_bandwidth']['url'] ?? null,
            'thumb'    => $media['low_resolution']['url'] ?? null,
            'standard' => $media['standard_resolution']['url'] ?? null,
        ];
    }

    private static function extractImage($media)
    {
        return [
            'type'     => 'image',
            'low'      => $media['low_resolution']['url'] ?? null,
            'thumb'    => $media['thumbnail']['url'] ?? null,
            'standard' => $media['standard_resolution']['url'] ?? null,
        ];
    }
}