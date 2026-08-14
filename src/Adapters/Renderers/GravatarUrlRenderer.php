<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Adapters\Renderers;

use RuntimeException;
use Simtabi\Laranail\Avatar\Adapters\Sources\GravatarSource;
use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Enums\Format;

/**
 * An identity with an email, rendered as a Gravatar URL.
 *
 * Produces a link rather than bytes, which is why {@see Avatar} carries that
 * distinction — a caller cannot tell from a plain string whether to put it in
 * `src` or echo it inline.
 *
 * Makes no network call. Whether the address actually has an image is a
 * question only Gravatar can answer, and answering it here would mean an HTTP
 * request inside a template render.
 */
final readonly class GravatarUrlRenderer implements AvatarRenderer
{
    public function __construct(
        private GravatarSource $gravatar = new GravatarSource,
    ) {}

    public function supports(Format $format): bool
    {
        return $format === Format::Url;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'gravatar-url';
    }

    public function render(Identity $identity, Appearance $appearance): Avatar
    {
        $url = $identity->url ?? $this->gravatar->urlFor($identity, $appearance->size, $appearance->https);

        if ($url === null) {
            throw new RuntimeException(sprintf(
                'The [%s] renderer needs an identity with an email address or a URL; got neither for [%s]. '
                . 'Add the initials source to the chain if some subjects have no email.',
                $this->name(),
                $identity->key,
            ));
        }

        return Avatar::url($url, $identity);
    }
}
