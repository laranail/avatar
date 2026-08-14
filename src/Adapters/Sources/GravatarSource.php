<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Adapters\Sources;

use Simtabi\Laranail\Avatar\Contracts\AvatarSource;
use Simtabi\Laranail\Avatar\Data\Identity;

/**
 * An email address, resolved to a Gravatar.
 *
 * ## The hash is SHA-256, not MD5
 *
 * Gravatar has accepted SHA-256 since 2023 and now documents it as the
 * preferred form. The module this replaces used MD5, and an MD5 of an email
 * address is not a privacy measure — the input space is small enough that
 * commercial rainbow tables cover most real addresses, so a page that renders
 * `<img src="…/avatar/<md5>">` publishes its users' email addresses to anyone
 * who scrapes it.
 *
 * SHA-256 is not a fix for that in principle — the input space is the same size
 * — but it is what the service asks for, it is not precomputed at anything like
 * the same scale, and there is no reason to keep the weaker one. `md5` stays
 * reachable for an application that stored the old hash and needs to match it.
 */
final readonly class GravatarSource implements AvatarSource
{
    private const string HOST = 'gravatar.com/avatar/';

    public function __construct(
        /** `sha256` or `md5`. See the class docblock before choosing the latter. */
        private string $algorithm = 'sha256',
        /** Gravatar's own fallback when an address has no image. */
        private string $defaultImage = 'mp',
        private string $rating = 'g',
    ) {}

    public function identify(mixed $subject): ?Identity
    {
        $email = $this->emailFrom($subject);

        if ($email === null) {
            return null;
        }

        return new Identity(
            key: $email,
            label: $this->labelFrom($subject),
            email: $email,
        );
    }

    public function name(): string
    {
        return 'gravatar';
    }

    /**
     * The Gravatar URL for an identity.
     *
     * `https` unconditionally unless the caller asked otherwise. The two
     * modules merged here disagreed — one defaulted true, two of the other's
     * methods defaulted false — so the same application emitted both, and the
     * `http` one is a mixed-content warning on every page that shows it.
     */
    public function urlFor(Identity $identity, int $size, bool $https = true, ?string $defaultImage = null): ?string
    {
        if ($identity->email === null) {
            return null;
        }

        $query = http_build_query([
            's' => $size,
            'r' => $this->rating,
            'd' => $defaultImage ?? $this->defaultImage,
        ]);

        return ($https ? 'https://' : 'http://') . self::HOST . $this->hash($identity->email) . '?' . $query;
    }

    /**
     * Gravatar's documented normalisation: trim, then lower-case, then hash.
     *
     * Both steps matter. The service hashes the normalised form, so hashing
     * `Alice@Example.com ` as given produces a URL that resolves to nobody's
     * avatar — silently, since Gravatar answers with the default image rather
     * than a 404.
     */
    public function hash(string $email): string
    {
        $normalised = mb_strtolower(trim($email), 'UTF-8');

        return $this->algorithm === 'md5'
            ? md5($normalised)
            : hash('sha256', $normalised);
    }

    private function emailFrom(mixed $subject): ?string
    {
        $candidate = match (true) {
            is_string($subject) => $subject,
            is_object($subject) && isset($subject->email) && is_string($subject->email) => $subject->email,
            default => null,
        };

        if ($candidate === null) {
            return null;
        }

        $candidate = trim($candidate);

        // Not "contains an @". An invalid address hashes to a URL that returns
        // the default image, so a typo becomes a silently generic avatar
        // instead of falling through to the initials source that would have
        // rendered the person's name.
        return filter_var($candidate, FILTER_VALIDATE_EMAIL) === false ? null : $candidate;
    }

    private function labelFrom(mixed $subject): ?string
    {
        if (is_object($subject) && isset($subject->name) && is_string($subject->name)) {
            return $subject->name;
        }

        return null;
    }
}
