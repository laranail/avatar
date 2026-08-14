# Configuration

Every key in `config/laranail/avatar.php`, read as `config('laranail.avatar.*')`.

Nothing has to be published — the package merges its own defaults, and a partial
published file keeps the packaged value for anything it omits.

## `chain`

```php
'chain' => ['gravatar', 'initials'],
```

Sources are tried **in order** until one identifies the subject, and that
ordering *is* the fallback policy. `['gravatar', 'initials']` means "a Gravatar
when there is an email address, otherwise draw the name".

Neither source contains a line about the other, which is what lets you reorder
them, drop one, or register your own without touching either.

> **End the chain with `initials`.** It accepts anything with a name and never
> passes, so a chain ending there always resolves to something drawable. A chain
> that can fall off the end has a case where your page renders nothing.

## `renderer` and `format`

```php
'renderer' => env('AVATAR_RENDERER', 'svg'),
'format'   => env('AVATAR_FORMAT', 'svg'),
```

| Renderer | Emits | Needs |
|---|---|---|
| `svg` | An inline SVG data URI | nothing |
| `gravatar-url` | A `https://gravatar.com/avatar/…` link | nothing |

`svg` is the default and needs no GD, no Imagick, no `intervention/image` and no
font file. It is also resolution-independent, so one render serves a 24-pixel
list row and a retina profile header.

Raster formats (`png`, `jpeg`, `webp`) need `intervention/image`. Ask for one
without it and the manager says so rather than emitting a broken image.

## Appearance defaults

```php
'size'    => (int) env('AVATAR_SIZE', 100),
'shape'   => env('AVATAR_SHAPE', 'circle'),   // circle | square | rounded
'palette' => null,                             // null = the shipped 15
```

Defaults **only**. Every one is overridable per call on the builder, which
returns a new instance each time — so a component that wants 32px circles cannot
change what the rest of the page renders.

### The palette

The 15 shipped colours are chosen for contrast against white text. Random hex
would eventually produce near-white, and white initials on near-white is an
invisible avatar.

If you supply your own, note that the foreground is computed by **WCAG relative
luminance** against whichever background was picked — so a light custom colour
gets dark text automatically. What the package cannot do is rescue a palette
with no contrast in it at all.

## `https`

```php
'https' => env('AVATAR_HTTPS', true),
```

Generated URLs are `https` regardless of the incoming request's scheme, so an
avatar on a page served over http does not become a mixed-content warning for
everyone else.

Set false only if you genuinely need plain http. The two modules this package
merges disagreed — one defaulted true, two methods of the other defaulted false
— so one application emitted both.

## Environment variables

| Variable | Default |
|---|---|
| `AVATAR_RENDERER` | `svg` |
| `AVATAR_FORMAT` | `svg` |
| `AVATAR_SIZE` | `100` |
| `AVATAR_SHAPE` | `circle` |
| `AVATAR_HTTPS` | `true` |

---
[← Docs index](../README.md#documentation)
