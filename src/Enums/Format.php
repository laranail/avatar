<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Enums;

enum Format: string
{
    /** Not an image — a link to one, which is what Gravatar and friends return. */
    case Url = 'url';

    case Svg = 'svg';
    case Png = 'png';
    case Jpeg = 'jpeg';
    case Webp = 'webp';

    public function mimeType(): string
    {
        return match ($this) {
            self::Url => 'text/uri-list',
            self::Svg => 'image/svg+xml',
            self::Png => 'image/png',
            self::Jpeg => 'image/jpeg',
            self::Webp => 'image/webp',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Url => '',
            self::Svg => 'svg',
            self::Png => 'png',
            self::Jpeg => 'jpg',
            self::Webp => 'webp',
        };
    }

    /**
     * Whether producing this format needs a raster image library.
     *
     * SVG is markup, so it needs nothing — which is what lets this package work
     * with no GD, no Imagick and no `intervention/image`.
     */
    public function needsRasteriser(): bool
    {
        return match ($this) {
            self::Url, self::Svg => false,
            self::Png, self::Jpeg, self::Webp => true,
        };
    }

    public static function resolve(string $value): ?self
    {
        return match (strtolower(trim($value))) {
            'url' => self::Url,
            'svg' => self::Svg,
            'png' => self::Png,
            'jpg', 'jpeg' => self::Jpeg,
            'webp' => self::Webp,
            default => null,
        };
    }
}
