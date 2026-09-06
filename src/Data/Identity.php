<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Data;

/**
 * Who an avatar is for, as every source presents it.
 *
 * The contract between the two seams: a source fills this, a renderer reads it,
 * and neither knows anything about the other.
 *
 * `$label` is what initials are derived from — a person's name, a team's name.
 * `$email` is present only when the source had one, and `$url` only when the
 * source resolved to an image directly. All three can coexist: a Gravatar
 * identity carries the email *and* the URL *and* a label to fall back to when
 * the address has no image.
 */
final readonly class Identity
{
    /**
     * @param string $key a stable identifier for caching and for colour
     *                    selection — the email, the model key, the label
     * @param string|null $label human-readable, for initials
     * @param string|null $email a validated address, when there is one
     * @param string|null $url a resolved image URL, when there is one
     */
    public function __construct(
        public string $key,
        public ?string $label = null,
        public ?string $email = null,
        public ?string $url = null,
    ) {}

    public function hasUrl(): bool
    {
        return $this->url !== null && $this->url !== '';
    }

    /**
     * The initials for this identity, up to `$count` characters.
     *
     * Multibyte-aware throughout. `substr()` on a UTF-8 name returns a broken
     * byte sequence rather than a letter, which renders as a replacement
     * character — so a name in any non-ASCII script gets a mojibake avatar.
     */
    public function initials(int $count = 2, bool $uppercase = true): string
    {
        $label = trim($this->label ?? '');

        if ($label === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach ($words as $word) {
            if (mb_strlen($initials, 'UTF-8') >= $count) {
                break;
            }

            $initials .= mb_substr($word, 0, 1, 'UTF-8');
        }

        // A single long word — a username, a mononym — yields one initial from
        // the loop above. Taking the first `$count` characters of it reads
        // better than a lone letter in a circle.
        if (mb_strlen($initials, 'UTF-8') < $count && count($words) === 1) {
            $initials = mb_substr($words[0], 0, $count, 'UTF-8');
        }

        return $uppercase ? mb_strtoupper($initials, 'UTF-8') : $initials;
    }
}
