<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Data;

use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\Enums\Shape;

/**
 * How an avatar should look, as one immutable value.
 *
 * The class this replaces held these as nineteen mutable properties with
 * nineteen setters returning `$this`, so a service shared through the container
 * carried whatever the last caller had configured. Two pages rendering avatars
 * in one request could disagree about the size, and the second one won.
 *
 * Every method here returns a new instance. A partially-configured appearance
 * is safe to keep and reuse, which is the property that makes a shared default
 * possible at all.
 */
final readonly class Appearance
{
    /**
     * @param list<string> $palette background colours to choose from
     */
    private function __construct(
        public int $size = 100,
        public Shape $shape = Shape::Circle,
        public Format $format = Format::Svg,
        public int $initials = 2,
        public bool $uppercase = true,
        public ?string $background = null,
        public ?string $foreground = null,
        public array $palette = self::DEFAULT_PALETTE,
        public int $borderWidth = 0,
        public ?string $borderColour = null,
        public string $fontFamily = 'sans-serif',
        public ?string $fontPath = null,
        public int $quality = 90,
        public bool $https = true,
    ) {}

    /**
     * Colours chosen for contrast against white text at typical avatar sizes.
     *
     * Deliberately not random hex: a palette that can produce `#f5f5f5` renders
     * white initials on near-white, which is invisible and is what an unbounded
     * random colour eventually does.
     *
     * @var list<string>
     */
    public const array DEFAULT_PALETTE = [
        '#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e',
        '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50',
        '#f39c12', '#d35400', '#c0392b', '#7f8c8d', '#e74c3c',
    ];

    public static function default(): self
    {
        return new self;
    }

    public function withSize(int $size): self
    {
        return $this->with(size: max(1, $size));
    }

    public function withShape(Shape $shape): self
    {
        return $this->with(shape: $shape);
    }

    public function withFormat(Format $format): self
    {
        return $this->with(format: $format);
    }

    public function withInitials(int $count): self
    {
        return $this->with(initials: max(1, min(4, $count)));
    }

    public function withUppercase(bool $uppercase = true): self
    {
        return $this->with(uppercase: $uppercase);
    }

    public function withColours(?string $background = null, ?string $foreground = null): self
    {
        return $this->with(background: $background, foreground: $foreground);
    }

    /**
     * @param list<string> $palette
     */
    public function withPalette(array $palette): self
    {
        return $this->with(palette: $palette === [] ? self::DEFAULT_PALETTE : array_values($palette));
    }

    public function withBorder(int $width, ?string $colour = null): self
    {
        return $this->with(borderWidth: max(0, $width), borderColour: $colour);
    }

    public function withFontFamily(string $family): self
    {
        return $this->with(fontFamily: $family);
    }

    public function withFontPath(?string $path): self
    {
        return $this->with(fontPath: $path);
    }

    public function withQuality(int $quality): self
    {
        return $this->with(quality: max(1, min(100, $quality)));
    }

    /**
     * Force plain HTTP for generated URLs.
     *
     * Named for what it does and defaulting to `true` everywhere, because the
     * two modules this package merges disagreed: Gravatar defaulted `https =
     * true` while two of Avatar's bridge methods defaulted it `false`. So the
     * same application emitted `http://` avatars from one call and `https://`
     * from another, and the first is a mixed-content warning on every page that
     * uses it.
     */
    public function withHttps(bool $https = true): self
    {
        return $this->with(https: $https);
    }

    /**
     * The background colour for an identity, chosen deterministically.
     *
     * The same person gets the same colour on every page and every request,
     * which is the whole point — an avatar that changes colour when the cache
     * expires reads as a different person.
     *
     * `crc32` rather than a cryptographic hash: this picks an index in a
     * fifteen-entry list, and the only property that matters is that it is
     * stable and evenly spread.
     */
    public function backgroundFor(Identity $identity): string
    {
        if ($this->background !== null) {
            return $this->background;
        }

        $palette = $this->palette === [] ? self::DEFAULT_PALETTE : $this->palette;

        return $palette[crc32($identity->key) % count($palette)];
    }

    /**
     * The text colour, chosen for contrast against the background.
     *
     * Not a fixed white. `#f39c12` and `#7f8c8d` are both in the default
     * palette and both take white text badly — the initials wash out at small
     * sizes, which is where avatars mostly live. Relative luminance decides,
     * per WCAG's definition, so the choice tracks the palette rather than being
     * tuned to it.
     *
     * Rector flagged the earlier version of this for ignoring its argument, and
     * it was right: the parameter existed for symmetry with `backgroundFor()`
     * and did nothing. Making it load-bearing was the better answer than
     * deleting it.
     */
    public function foregroundFor(Identity $identity): string
    {
        if ($this->foreground !== null) {
            return $this->foreground;
        }

        return $this->luminance($this->backgroundFor($identity)) > 0.5 ? '#1f2933' : '#ffffff';
    }

    /**
     * Relative luminance of a hex colour, 0 (black) to 1 (white).
     *
     * The sRGB gamma correction matters: a naive `(r+g+b)/3` calls `#0000ff`
     * mid-bright when it is nearly black to the eye, and would put dark text on
     * it.
     */
    private function luminance(string $hex): float
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) < 6 || preg_match('/^[0-9a-fA-F]{6}/', $hex) !== 1) {
            // Not a colour this can read. Assume dark, which yields white text —
            // the safer default, since most palettes are dark.
            return 0.0;
        }

        $channels = [];

        foreach ([0, 2, 4] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * PHP 8.4 has no `clone with`, so the constructor is re-invoked with the
     * current values as defaults. Verbose, and it keeps the class readonly on
     * the floor this package supports — `clone ($this, [...])` is 8.5 only.
     *
     * @param list<string>|null $palette
     */
    private function with(
        ?int $size = null,
        ?Shape $shape = null,
        ?Format $format = null,
        ?int $initials = null,
        ?bool $uppercase = null,
        ?string $background = null,
        ?string $foreground = null,
        ?array $palette = null,
        ?int $borderWidth = null,
        ?string $borderColour = null,
        ?string $fontFamily = null,
        ?string $fontPath = null,
        ?int $quality = null,
        ?bool $https = null,
    ): self {
        return new self(
            size: $size ?? $this->size,
            shape: $shape ?? $this->shape,
            format: $format ?? $this->format,
            initials: $initials ?? $this->initials,
            uppercase: $uppercase ?? $this->uppercase,
            background: $background ?? $this->background,
            foreground: $foreground ?? $this->foreground,
            palette: $palette ?? $this->palette,
            borderWidth: $borderWidth ?? $this->borderWidth,
            borderColour: $borderColour ?? $this->borderColour,
            fontFamily: $fontFamily ?? $this->fontFamily,
            fontPath: $fontPath ?? $this->fontPath,
            quality: $quality ?? $this->quality,
            https: $https ?? $this->https,
        );
    }
}
