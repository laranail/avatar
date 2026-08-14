<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Avatar\AvatarBuilder;
use Simtabi\Laranail\Avatar\AvatarManager;
use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;

/**
 * @method static AvatarBuilder builder(?string $renderer = null)
 * @method static \Simtabi\Laranail\Avatar\Data\Avatar for(mixed $subject)
 * @method static AvatarRenderer renderer(string $name)
 * @method static AvatarManager extendSource(string $name, Closure $factory)
 * @method static AvatarManager extendRenderer(string $name, Closure $factory)
 * @method static AvatarManager chain(list<string> $names)
 * @method static list<string> availableSources()
 * @method static list<string> availableRenderers()
 *
 * @see AvatarManager
 */
final class Avatar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AvatarManager::class;
    }
}
