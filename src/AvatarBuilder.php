<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar;

use RuntimeException;
use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\Enums\Shape;

/**
 * The fluent entry point: a subject in, an {@see Avatar} out.
 *
 * **Immutable.** The class this replaces held nineteen mutable properties with
 * setters returning `$this`, and was registered as a container singleton — so a
 * service shared across a request carried whatever the last caller had
 * configured, and two components rendering avatars on one page disagreed about
 * the size with the second one winning. Every method here returns a new
 * instance, which is what makes a shared, partially-configured default safe.
 *
 * Sources are tried in order until one identifies the subject. That ordering is
 * the whole configuration of "Gravatar, falling back to initials" — neither
 * source knows the other exists.
 */
final readonly class AvatarBuilder
{
    /**
     * @param  list<AvatarSource>  $sources  tried in order
     */
    public function __construct(
        private array $sources,
        private AvatarRenderer $renderer,
        private Appearance $appearance,
    ) {}

    // -----------------------------------------------------------------------
    // Appearance — each returns a new builder
    // -----------------------------------------------------------------------

    public function size(int $pixels): self
    {
        return $this->withAppearance($this->appearance->withSize($pixels));
    }

    public function shape(Shape|string $shape): self
    {
        $resolved = $shape instanceof Shape ? $shape : Shape::resolve($shape);

        return $resolved instanceof Shape ? $this->withAppearance($this->appearance->withShape($resolved)) : $this;
    }

    public function circle(): self
    {
        return $this->shape(Shape::Circle);
    }

    public function square(): self
    {
        return $this->shape(Shape::Square);
    }

    public function rounded(): self
    {
        return $this->shape(Shape::Rounded);
    }

    public function initials(int $count): self
    {
        return $this->withAppearance($this->appearance->withInitials($count));
    }

    public function colours(?string $background = null, ?string $foreground = null): self
    {
        return $this->withAppearance($this->appearance->withColours($background, $foreground));
    }

    /**
     * @param  list<string>  $palette
     */
    public function palette(array $palette): self
    {
        return $this->withAppearance($this->appearance->withPalette($palette));
    }

    public function border(int $width, ?string $colour = null): self
    {
        return $this->withAppearance($this->appearance->withBorder($width, $colour));
    }

    public function fontFamily(string $family): self
    {
        return $this->withAppearance($this->appearance->withFontFamily($family));
    }

    public function quality(int $quality): self
    {
        return $this->withAppearance($this->appearance->withQuality($quality));
    }

    /**
     * Emit plain HTTP URLs.
     *
     * Exists, is named for what it does, and is the only way down. Generated
     * URLs are `https` regardless of the incoming request scheme, so an avatar
     * on a page served over http does not become a mixed-content warning for
     * everyone else.
     */
    public function withHttps(bool $https = true): self
    {
        return $this->withAppearance($this->appearance->withHttps($https));
    }

    // -----------------------------------------------------------------------
    // Seams
    // -----------------------------------------------------------------------

    public function renderer(AvatarRenderer $renderer): self
    {
        return new self($this->sources, $renderer, $this->appearance->withFormat(
            $renderer->supports($this->appearance->format) ? $this->appearance->format : Format::Svg,
        ));
    }

    /**
     * @param  list<AvatarSource>  $sources
     */
    public function sources(array $sources): self
    {
        return new self(array_values($sources), $this->renderer, $this->appearance);
    }

    /**
     * Add a source to the front of the chain, so it is tried first.
     */
    public function preferring(AvatarSource $source): self
    {
        return new self([$source, ...$this->sources], $this->renderer, $this->appearance);
    }

    // -----------------------------------------------------------------------
    // Terminals
    // -----------------------------------------------------------------------

    /**
     * Resolve the subject and render it.
     */
    public function for(mixed $subject): Avatar
    {
        return $this->renderer->render($this->identify($subject), $this->appearance);
    }

    /**
     * A `src` attribute value — a URL or a data URI, whichever this renders.
     */
    public function url(mixed $subject): string
    {
        return $this->for($subject)->src();
    }

    /**
     * The identity a subject resolves to, without rendering it.
     *
     * Useful on its own: a caller that wants the Gravatar hash, or wants to
     * know whether the subject had an email at all, should not have to render
     * an image to find out.
     */
    public function identify(mixed $subject): Identity
    {
        foreach ($this->sources as $source) {
            $identity = $source->identify($subject);

            if ($identity instanceof Identity) {
                return $identity;
            }
        }

        throw new RuntimeException(sprintf(
            'No source could identify the subject. Tried: %s. The initials source accepts anything with '
            .'a name, so a chain ending in it always resolves.',
            $this->sources === []
                ? 'none — the chain is empty'
                : implode(', ', array_map(static fn (AvatarSource $s): string => $s->name(), $this->sources)),
        ));
    }

    public function appearance(): Appearance
    {
        return $this->appearance;
    }

    private function withAppearance(Appearance $appearance): self
    {
        return new self($this->sources, $this->renderer, $appearance);
    }
}
