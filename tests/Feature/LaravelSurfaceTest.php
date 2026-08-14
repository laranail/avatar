<?php

declare(strict_types=1);

use Simtabi\Laranail\Avatar\AvatarManager;
use Simtabi\Laranail\Avatar\Facades\Avatar;

it('publishes config to the vendor-namespaced key', function (): void {
    expect(config('laranail.avatar.renderer'))->toBe('svg')
        ->and(config('avatar.renderer'))->toBeNull();
});

it('resolves the manager as a singleton', function (): void {
    expect(app(AvatarManager::class))->toBe(app(AvatarManager::class));
});

it('renders through the facade', function (): void {
    expect(Avatar::for('Imani Manyara')->bytes())->toContain('IM');
});

it('hands out a fresh builder each time', function (): void {
    // The manager is mutable because registration is a boot-time act; the
    // builder is not because configuration is a per-call one, and that is the
    // one that must not leak between callers.
    expect(Avatar::builder())->not->toBe(Avatar::builder());
});

it('registers no routes', function (): void {
    // Rendering images on a public endpoint is a CPU-exhaustion vector, and an
    // application that wants to serve avatars over HTTP knows where its auth
    // and rate limits live.
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_contains((string) $route->uri(), 'avatar'));

    expect($routes)->toBeEmpty();
});

it('takes its defaults from config', function (): void {
    config()->set('laranail.avatar.size', 64);
    config()->set('laranail.avatar.shape', 'square');
    app()->forgetInstance(AvatarManager::class);

    expect(Avatar::builder()->appearance()->size)->toBe(64)
        ->and(Avatar::for('AB')->bytes())->toContain('<rect');
});

it('keeps https on unless config explicitly turns it off', function (): void {
    expect(Avatar::builder('gravatar-url')->for('imani@simtabi.com')->src())->toStartWith('https://');

    config()->set('laranail.avatar.https', false);

    // Both are needed: forgetInstance() clears the container binding, and the
    // facade keeps its own resolved instance beside it. Standard Laravel
    // behaviour, and the reason a config change mid-request does not take.
    app()->forgetInstance(AvatarManager::class);
    Avatar::clearResolvedInstances();

    expect(Avatar::builder('gravatar-url')->for('imani@simtabi.com')->src())->toStartWith('http://');
});
