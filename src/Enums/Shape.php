<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Enums;

enum Shape: string
{
    case Circle = 'circle';
    case Square = 'square';
    case Rounded = 'rounded';

    public static function resolve(string $value): ?self
    {
        return self::tryFrom(strtolower(trim($value)));
    }

    /**
     * The CSS corner radius for this shape at the given size, in pixels.
     *
     * A circle is half the size; rounded is an eighth, which reads as a
     * deliberate corner rather than an almost-circle at every scale.
     */
    public function radius(int $size): int
    {
        return match ($this) {
            self::Circle => intdiv($size, 2),
            self::Rounded => intdiv($size, 8),
            self::Square => 0,
        };
    }
}
