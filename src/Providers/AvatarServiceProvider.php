<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Providers;

use Override;
use Simtabi\Laranail\Avatar\Enums\Shape;
use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\AvatarManager;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Entry point for laranail/avatar.
 *
 * Config is vendor-namespaced by default: the file publishes to
 * `config/laranail/avatar.php` and application code reads
 * `config('laranail.avatar.*')`.
 *
 * **Registers no routes.** Rendering images on a public endpoint is a
 * CPU-exhaustion vector, and `store()` plus the host's own route covers every
 * real case — an application that wants to serve avatars over HTTP knows where
 * its auth and rate limits live, and a package does not.
 *
 * @internal Auto-discovered framework wiring; not part of the public API.
 */
final class AvatarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/avatar')
            ->setPublishTagId('avatar')
            ->hasConfigFile('avatar');
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(AvatarManager::class, static function (Application $app): AvatarManager {
            /** @var array<string, mixed> $config */
            $config = (array) $app->make('config')->get('laranail.avatar', []);

            $manager = new AvatarManager(
                self::appearanceFrom($config),
                is_string($config['renderer'] ?? null) ? $config['renderer'] : 'svg',
            );

            $chain = $config['chain'] ?? null;

            if (is_array($chain) && $chain !== []) {
                $manager->chain(array_values(array_filter($chain, is_string(...))));
            }

            return $manager;
        });
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function appearanceFrom(array $config): Appearance
    {
        $appearance = Appearance::default();

        if (is_numeric($config['size'] ?? null)) {
            $appearance = $appearance->withSize((int) $config['size']);
        }

        if (is_string($config['shape'] ?? null)) {
            $shape = Shape::resolve($config['shape']);
            $appearance = $shape instanceof Shape ? $appearance->withShape($shape) : $appearance;
        }

        if (is_string($config['format'] ?? null)) {
            $format = Format::resolve($config['format']);
            $appearance = $format instanceof Format ? $appearance->withFormat($format) : $appearance;
        }

        if (is_array($config['palette'] ?? null)) {
            $appearance = $appearance->withPalette(array_values(array_filter($config['palette'], is_string(...))));
        }

        // https defaults true and only an explicit false moves it, because the
        // two modules merged here disagreed and the http one is a
        // mixed-content warning on every page that shows it.
        if (($config['https'] ?? true) === false) {
            return $appearance->withHttps(false);
        }

        return $appearance;
    }
}
