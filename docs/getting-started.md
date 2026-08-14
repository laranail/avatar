# Getting started

## One call

```php
use Simtabi\Laranail\Avatar\Facades\Avatar;

Avatar::for('ada@example.com')->src();
// https://gravatar.com/avatar/b5fc85…?s=100&r=g&d=mp

Avatar::for('Ada Lovelace')->src();
// data:image/svg+xml;base64,…  ← initials, drawn locally
```

Same call, two answers, because the **chain** decides. `['gravatar',
'initials']` means "a Gravatar when there is an email address, otherwise draw
the name" — and neither source contains a line about the other.

## The two seams

This is the shape worth understanding, because everything else follows from it:

| Contract | Answers | Ships |
|---|---|---|
| `AvatarSource` | *Where the identity comes from* | `gravatar`, `initials` |
| `AvatarRenderer` | *How pixels get made* | `svg`, `gravatar-url` |

They vary **independently**. A Gravatar identity can fall back to
locally-rendered initials; an initials identity can render as SVG or PNG.
Collapsing them into one "avatar driver" is what produced the 1,019-line
god-class this package replaces.

So: a new provider is a `Sources/` entry. A new output format is a `Renderers/`
entry. Neither touches the other.

## Customising

Every setter returns a **new** builder, so a partly-configured one is safe to
hold and reuse:

```php
$base = Avatar::builder()->size(64)->rounded();

$dark  = $base->colours(background: '#111111', foreground: '#ffffff');
$light = $base->colours(background: '#ffffff', foreground: '#111111');
// $base is unchanged
```

| Method | Effect |
|---|---|
| `size(int $pixels)` | Output size |
| `circle()` / `square()` / `rounded()` | Shape |
| `shape(Shape\|string)` | The same, by value |
| `initials(int $count)` | How many letters |
| `colours(?string $bg, ?string $fg)` | Fixed colours |
| `palette(array $palette)` | The set to pick from |
| `border(int $width, ?string $colour)` | A border |
| `fontFamily(string $family)` | For the SVG renderer |
| `quality(int $quality)` | Raster only |
| `withHttps(bool $https = true)` | See below |
| `renderer(AvatarRenderer)` | Override the renderer |
| `sources(array $sources)` / `preferring(AvatarSource)` | Override the chain |

Terminals: `for($subject)` returns an `Avatar`, `url($subject)` a string,
`identify($subject)` the `Identity` without rendering.

## Colours are derived, not random

```php
Avatar::for('ada@example.com')->src();   // the same colour every time
```

The background is picked from the palette by hashing the identity's `key`, so
one person keeps one colour across requests, servers and deploys. A random
colour per render makes a list of users flicker on every page load.

The foreground is then chosen by **WCAG relative luminance** against that
background — real contrast maths on the sRGB gamma curve, not "white unless the
colour is light-ish". The 15 shipped palette colours are all dark enough for
white text, but a custom palette will not be, and a name nobody can read is not
an avatar.

## https is the default

```php
Avatar::builder()->withHttps(false);   // the only way down, and it is named
```

Generated URLs are `https` regardless of the incoming request's scheme, so an
avatar on an http page does not emit a mixed-content warning.

The two modules merged into this package disagreed about that — one defaulted
true, two methods of the other defaulted false — so the same application emitted
both.

## Where to go next

- [Configuration](configuration.md) — every key
- [Sources](tools/sources.md) — the identity seam
- [Renderers](tools/renderers.md) — the pixel seam
- [The builder](tools/builder.md) — the full API
- [Storing an avatar](recipes/store-an-avatar.md)

---
[← Docs index](../README.md#documentation)
