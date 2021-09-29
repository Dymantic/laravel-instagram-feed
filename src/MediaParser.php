<?php


namespace Dymantic\InstagramFeed;


class MediaParser
{
    public static function parseItem($media, $ignore_video = false): ?InstagramMedia
    {
        return match ($media['media_type']) {
            'IMAGE' => static::parseAsImage($media),
            'VIDEO' => static::parseAsVideo($media, $ignore_video),
            'CAROUSEL_ALBUM' => static::parseAsCarousel($media, $ignore_video),
            default => null,
        };
    }

    private static function parseAsImage($media): ?InstagramMedia
    {
        return InstagramMedia::newImage([
            'url'       => $media['media_url'],
            'id'        => $media['id'],
            'caption'   => $media['caption'] ?? '',
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'] ?? ''
        ]);
    }

    private static function parseAsVideo($media, $ignore_video): ?InstagramMedia
    {
        if ($ignore_video) {
            return null;
        }

        return InstagramMedia::newVideo([
            'url'           => $media['media_url'],
            'id'            => $media['id'],
            'caption'       => $media['caption'] ?? '',
            'permalink'     => $media['permalink'],
            'timestamp'     => $media['timestamp'] ?? '',
            'thumbnail_url' => $media['thumbnail_url'],
        ]);
    }

    private static function parseAsCarousel($media, $ignore_video): ?InstagramMedia
    {
        $children = collect($media['children']['data'])
            ->filter(function ($child) use ($ignore_video) {
                return $child['media_type'] === 'IMAGE' || (!$ignore_video);
            });

        if (!$children->count()) {
            return null;
        }

        $use = $children->first();

        return InstagramMedia::newCarousel([
            'type'      => strtolower($use['media_type']),
            'url'       => $use['media_url'],
            'id'        => $media['id'],
            'caption'   => $media['caption'] ?? '',
            'permalink' => $media['permalink'],
            'timestamp' => $media['timestamp'] ?? '',
            'children'  => $children->map(function ($child) {
                return [
                    'type' => strtolower($child['media_type']),
                    'url'  => $child['media_url'],
                    'id'   => $child['id'],
                ];
            })->values()->all(),
        ]);

    }
}
