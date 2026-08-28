<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar;

use Closure;
use RuntimeException;
use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;
use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;
use Simtabi\Laranail\Avatar\Adapters\Renderers\SvgRenderer;
use Simtabi\Laranail\Avatar\Adapters\Sources\GravatarSource;
use Simtabi\Laranail\Avatar\Adapters\Sources\InitialsSource;
use Simtabi\Laranail\Avatar\Adapters\Renderers\GravatarUrlRenderer;

/**
 * Holds the registered sources and renderers, and hands out builders.
 *
 * Two registries because the seams are independent: a new provider is a source,
 * a new output format is a renderer, and neither needs the other to change.
 *
 * `extend*()` takes a **closure, not a class name**, for the same reason the
 * rest of the family does — a config string must never become something the
 * container instantiates by name.
 *
 * This class is mutable and the builder is not, deliberately. Registration is a
 * boot-time act in a provider; configuration is a per-call act in application
 * code, and that is the one that must not leak between callers.
 */
final class AvatarManager
{
    /** @var array<string, Closure(): AvatarSource> */
    private array $sources = [];

    /** @var array<string, Closure(): AvatarRenderer> */
    private array $renderers = [];

    /** @var list<string> */
    private array $chain = ['gravatar', 'initials'];

    /**
     * Not a promoted property: the parameter is nullable so callers can omit it
     * (Appearance's constructor is private, so it cannot be a default value),
     * while the property never is.
     */
    private Appearance $appearance;

    public function __construct(
        ?Appearance $appearance = null,
        private readonly string $defaultRenderer = 'svg',
    ) {
        $this->appearance = $appearance ?? Appearance::default();

        $this->extendSource('gravatar', static fn (): AvatarSource => new GravatarSource);
        $this->extendSource('initials', static fn (): AvatarSource => new InitialsSource);

        $this->extendRenderer('svg', static fn (): AvatarRenderer => new SvgRenderer);
        $this->extendRenderer('gravatar-url', static fn (): AvatarRenderer => new GravatarUrlRenderer);
    }

    /**
     * @param Closure(): AvatarSource $factory
     */
    public function extendSource(string $name, Closure $factory): self
    {
        $this->sources[$name] = $factory;

        return $this;
    }

    /**
     * @param Closure(): AvatarRenderer $factory
     */
    public function extendRenderer(string $name, Closure $factory): self
    {
        $this->renderers[$name] = $factory;

        return $this;
    }

    /**
     * Set the order sources are tried in.
     *
     * This ordering *is* the fallback policy. `['gravatar', 'initials']` means
     * "a Gravatar when there is an email, otherwise draw the name", and neither
     * source contains a line about the other.
     *
     * @param list<string> $names
     */
    public function chain(array $names): self
    {
        $this->chain = array_values($names);

        return $this;
    }

    public function defaults(Appearance $appearance): self
    {
        $this->appearance = $appearance;

        return $this;
    }

    /**
     * A fresh builder. Everything after this point is immutable.
     */
    public function builder(?string $renderer = null): AvatarBuilder
    {
        return new AvatarBuilder(
            $this->resolveChain(),
            $this->renderer($renderer ?? $this->defaultRenderer),
            $this->appearance,
        );
    }

    /**
     * The shortest path: subject in, avatar out, using the configured defaults.
     */
    public function for(mixed $subject): Avatar
    {
        return $this->builder()->for($subject);
    }

    public function renderer(string $name): AvatarRenderer
    {
        $factory = $this->renderers[$name] ?? throw new RuntimeException(sprintf(
            'Unknown avatar renderer [%s]. Registered: %s. Add one with extendRenderer(), which takes a closure.',
            $name,
            implode(', ', array_keys($this->renderers)),
        ));

        $renderer = $factory();

        // Checked here rather than at the point of writing bytes, so a missing
        // image extension surfaces as "install this" and not as a fatal three
        // frames into a template render.
        if (! $renderer->isAvailable()) {
            throw new RuntimeException(sprintf(
                'The [%s] avatar renderer cannot run in this environment. The svg renderer needs nothing '
                . 'and is the default; the raster one needs intervention/image plus GD or Imagick.',
                $name,
            ));
        }

        return $renderer;
    }

    /**
     * @return list<string>
     */
    public function availableSources(): array
    {
        return array_keys($this->sources);
    }

    /**
     * @return list<string>
     */
    public function availableRenderers(): array
    {
        return array_keys($this->renderers);
    }

    /**
     * @return list<AvatarSource>
     */
    private function resolveChain(): array
    {
        $resolved = [];

        foreach ($this->chain as $name) {
            $factory = $this->sources[$name] ?? throw new RuntimeException(sprintf(
                'Unknown avatar source [%s] in the chain. Registered: %s.',
                $name,
                implode(', ', array_keys($this->sources)),
            ));

            $resolved[] = $factory();
        }

        return $resolved;
    }
}
