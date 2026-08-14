<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Contracts;

use Simtabi\Laranail\Avatar\Data\Identity;

/**
 * Where an avatar's identity comes from.
 *
 * One of the two seams, and deliberately separate from {@see AvatarRenderer}
 * because the two vary independently: a Gravatar identity can fall back to
 * locally-rendered initials when the address has no image, and an initials
 * identity can render as SVG or as a PNG. Collapsing them is what produced the
 * 1,019-line class this package replaces, where "which avatar" and "how to draw
 * it" were the same forty setters.
 *
 * A source answers *who*, and nothing about pixels.
 */
interface AvatarSource
{
    /**
     * Resolve the input into an identity, or null if this source cannot.
     *
     * Null is a pass, not a failure: it means "not mine", and the chain moves
     * on. A source that cannot handle the input but throws would make ordering
     * the chain a matter of luck.
     */
    public function identify(mixed $subject): ?Identity;

    /**
     * A short name, for config and for error messages.
     */
    public function name(): string;
}
