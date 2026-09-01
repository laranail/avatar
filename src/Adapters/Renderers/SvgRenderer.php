<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Adapters\Renderers;

use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\Enums\Shape;

/**
 * Initials as an SVG document.
 *
 * The default renderer, and the reason `intervention/image` is a `suggest`
 * rather than a dependency: this needs no GD, no Imagick and no font file. It
 * is also sharper — an SVG avatar is resolution-independent, so the same markup
 * serves a 24-pixel list row and a retina profile header.
 *
 * ## Escaping
 *
 * Initials come from user-supplied names and go into markup that a template
 * echoes inline, so this is an XSS surface. Everything interpolated is escaped
 * — the text with `htmlspecialchars`, the colours by pattern — because Blade's
 * `{!! !!}` is exactly how an inline SVG reaches a page.
 */
final readonly class SvgRenderer implements AvatarRenderer
{
    public function supports(Format $format): bool
    {
        return $format === Format::Svg;
    }

    public function isAvailable(): bool
    {
        // Markup, not pixels. There is nothing to be missing.
        return true;
    }

    public function name(): string
    {
        return 'svg';
    }

    public function render(Identity $identity, Appearance $appearance): Avatar
    {
        $size = $appearance->size;
        $initials = $identity->initials($appearance->initials, $appearance->uppercase);

        $background = $this->colour($appearance->backgroundFor($identity), '#34495e');
        $foreground = $this->colour($appearance->foregroundFor($identity), '#ffffff');

        // Roughly 40% of the box, which keeps two characters comfortably inside
        // a circle at every size. Bigger and a wide pair such as "WM" collides
        // with the edge.
        $fontSize = (int) round($size * 0.4);

        $shape = $this->shapeMarkup($appearance, $size, $background);
        $border = $this->borderMarkup($appearance, $size);

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d" '
            .'role="img" aria-label="%2$s">%3$s%4$s'
            .'<text x="50%%" y="50%%" dy=".35em" fill="%5$s" font-family="%6$s" font-size="%7$d" '
            .'font-weight="600" text-anchor="middle">%8$s</text></svg>',
            $size,
            $this->escape($identity->label ?? $initials),
            $shape,
            $border,
            $foreground,
            $this->escape($appearance->fontFamily),
            $fontSize,
            $this->escape($initials),
        );

        return Avatar::contents($svg, $identity, Format::Svg);
    }

    private function shapeMarkup(Appearance $appearance, int $size, string $background): string
    {
        if ($appearance->shape === Shape::Circle) {
            return sprintf(
                '<circle cx="%1$d" cy="%1$d" r="%1$d" fill="%2$s"/>',
                intdiv($size, 2),
                $background,
            );
        }

        return sprintf(
            '<rect width="%1$d" height="%1$d" rx="%2$d" ry="%2$d" fill="%3$s"/>',
            $size,
            $appearance->shape->radius($size),
            $background,
        );
    }

    private function borderMarkup(Appearance $appearance, int $size): string
    {
        if ($appearance->borderWidth <= 0) {
            return '';
        }

        $colour = $this->colour($appearance->borderColour ?? '#ffffff', '#ffffff');
        $inset = $appearance->borderWidth / 2;

        if ($appearance->shape === Shape::Circle) {
            return sprintf(
                '<circle cx="%1$d" cy="%1$d" r="%2$s" fill="none" stroke="%3$s" stroke-width="%4$d"/>',
                intdiv($size, 2),
                (string) ($size / 2 - $inset),
                $colour,
                $appearance->borderWidth,
            );
        }

        return sprintf(
            '<rect x="%1$s" y="%1$s" width="%2$s" height="%2$s" rx="%3$d" fill="none" stroke="%4$s" stroke-width="%5$d"/>',
            (string) $inset,
            (string) ($size - $appearance->borderWidth),
            $appearance->shape->radius($size),
            $colour,
            $appearance->borderWidth,
        );
    }

    /**
     * A colour, or the fallback.
     *
     * Pattern-matched rather than escaped. A colour goes into an SVG attribute
     * unquoted by any templating layer, so `#fff" onload="alert(1)` in a
     * configured palette would be markup rather than a colour. Allowing only
     * hex and the CSS keyword shape removes the question.
     */
    private function colour(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1) {
            return $value;
        }

        return preg_match('/^[a-zA-Z]{3,20}$/', $value) === 1 ? $value : $fallback;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
