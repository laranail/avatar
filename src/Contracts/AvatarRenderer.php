<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Contracts;

use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Data\Appearance;

/**
 * How an identity becomes something a browser can show.
 *
 * The second seam. A renderer answers *how*, and nothing about who — it is
 * handed a resolved {@see Identity} and an {@see Appearance} and produces either
 * a URL or bytes.
 *
 * Keeping this apart from {@see AvatarSource} is what lets a new provider be a
 * `Sources/` entry and a new output format a `Renderers/` entry, rather than
 * both being another forty methods on one class.
 */
interface AvatarRenderer
{
    /**
     * Whether this renderer can produce the requested format.
     *
     * Derived from what the renderer actually implements rather than declared
     * in a list — an SVG renderer cannot produce a PNG, and saying so here is
     * cheaper than failing at the point of writing bytes.
     */
    public function supports(Format $format): bool;

    /**
     * Render, returning a URL or a data payload.
     *
     * Which of the two is a property of the renderer, not of the caller, and
     * {@see Avatar} carries the distinction so a
     * consumer never has to guess whether it holds a link or an image.
     */
    public function render(Identity $identity, Appearance $appearance): Avatar;

    /**
     * Whether this renderer can run at all in the current environment.
     *
     * The raster renderer needs `intervention/image` plus GD or Imagick; the
     * SVG one needs nothing. This is what the manager checks before handing
     * back a renderer that would throw on first use.
     */
    public function isAvailable(): bool;

    /**
     * A short name, for config and for error messages.
     */
    public function name(): string;
}
