# The builder

`Avatar::builder()` returns an `AvatarBuilder` — immutable, clone-on-write.

```php
use Simtabi\Laranail\Avatar\Facades\Avatar;

Avatar::builder()->size(64)->rounded()->for($user);
```

## Immutable

Every setter returns a **new** instance:

```php
$base = Avatar::builder()->size(64);

$a = $base->circle();
$b = $base->square();
// $base is untouched; $a and $b are independent
```

The class this replaces mutated `$this`. That is fine for exactly one caller and
wrong the moment a second holds the same instance — a Blade component setting
`size(32)` for a list row silently resized the profile header rendered later in
the same request.

## Appearance

| Method | Effect |
|---|---|
| `size(int $pixels)` | Output size |
| `circle()` / `square()` / `rounded()` | Shape |
| `shape(Shape\|string $shape)` | The same, by value |
| `initials(int $count)` | How many letters to draw |
| `colours(?string $background, ?string $foreground)` | Fix both, or either |
| `palette(array $palette)` | The set to pick a background from |
| `border(int $width, ?string $colour)` | A border |
| `fontFamily(string $family)` | SVG only — a family name, not a file |
| `quality(int $quality)` | Raster only; the SVG renderer has no lossy step |
| `withHttps(bool $https = true)` | `true` unless you say otherwise |

## Wiring

| Method | Effect |
|---|---|
| `renderer(AvatarRenderer $renderer)` | Use this renderer, ignoring config |
| `sources(array $sources)` | Replace the chain |
| `preferring(AvatarSource $source)` | Put one source at the front |

`preferring()` is the common case — "try LDAP first, then whatever config says"
— and does not require restating the rest of the chain.

## Terminals

| Method | Returns |
|---|---|
| `for(mixed $subject)` | `Avatar` — the rendered result |
| `url(mixed $subject)` | `string` — shorthand for `for(…)->src()` |
| `identify(mixed $subject)` | `Identity` — resolved, **not** rendered |
| `appearance()` | The `Appearance` this builder would use |

`identify()` is the one to reach for when you want the identity without paying
for a render — deciding whether a user *has* a Gravatar, say, or reading the
initials for a text-only context.

## What `$subject` can be

Whatever the sources in the chain accept. The two that ship take:

- a **string** — an email address for `gravatar`, a name for `initials`;
- anything exposing an email or a name that a source knows how to read.

A source returns `null` for a subject it does not recognise, and the chain moves
on. Ending the chain with `initials` guarantees a result, because it accepts
anything with a name and never passes.

---
[← Docs index](../../README.md#documentation)
