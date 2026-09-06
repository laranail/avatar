<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Adapters\Sources;

use Stringable;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;

/**
 * Any label, resolved to itself.
 *
 * The terminal source: it accepts anything with a name and never returns null,
 * so a chain ending here always produces something to draw. That is what makes
 * "Gravatar, falling back to initials" expressible without either source
 * knowing about the other.
 */
final readonly class InitialsSource implements AvatarSource
{
    public function identify(mixed $subject): ?Identity
    {
        $label = match (true) {
            is_string($subject)                                                       => trim($subject),
            $subject instanceof Stringable                                            => trim((string) $subject),
            is_object($subject) && isset($subject->name) && is_string($subject->name) => trim($subject->name),
            default                                                                   => null,
        };

        if ($label === null || $label === '') {
            return null;
        }

        return new Identity(key: $label, label: $label);
    }

    public function name(): string
    {
        return 'initials';
    }
}
