# Installation

## Requirements

| | |
|---|---|
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel | `^13.0` |
| Extensions | **none** |

That last row is the point. The default renderer emits **SVG**, which is text —
no GD, no Imagick, nothing to compile. A CI job disables both extensions and
asserts initials still render, because the runner has GD built in and a renderer
that quietly required it would otherwise pass every other job.

## Install

```bash
composer require laranail/avatar
```

The service provider and the `Avatar` facade are discovered automatically.

## What you can publish

```bash
php artisan vendor:publish --tag="laranail::avatar-config"
```

→ `config/laranail/avatar.php`, read as `config('laranail.avatar.*')`. You do
not have to: the package merges its own defaults, and a partial published file
keeps the packaged value for anything it leaves out.

Publish tags are namespaced because `vendor:publish` keeps its tags in a flat
map — a bare `avatar` tag is a plausible collision, and the loser is replaced
silently.

## Optional: raster output

```bash
composer require intervention/image
```

Adds PNG, JPEG and WebP. **Only** needed if you want those; `intervention/image`
is a `suggest`, not a `require`, so the package installs and works without it.

If you ask for a format no available renderer supports, the manager says so
rather than emitting a broken image — `supports(Format)` and `isAvailable()` are
how a renderer declares what it can actually do.

## No routes

This package registers **none**, deliberately. Rendering images on a public
endpoint is a CPU-exhaustion vector nobody asked for: an unauthenticated caller
varying `?size=` walks straight into your image library.

`store()` plus your own route covers every real case, and the decision about who
may request an image belongs to your application.

## Verify

```php
use Simtabi\Laranail\Avatar\Facades\Avatar;

echo Avatar::for('ada@example.com')->src();
```

---
[← Docs index](../README.md#documentation)
