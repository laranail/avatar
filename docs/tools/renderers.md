# Renderers

An `AvatarRenderer` answers **how pixels get made**. Two ship.

```php
interface AvatarRenderer
{
    public function supports(Format $format): bool;
    public function isAvailable(): bool;
    public function render(Identity $identity, Appearance $appearance): Avatar;
    public function name(): string;
}
```

`supports()` and `isAvailable()` are how the manager decides what to call — so a
renderer cannot claim a format it has not implemented, and a missing extension
is a configuration state rather than a fatal three frames deep.

## `svg` — the default

Emits an inline SVG data URI. **Needs nothing**: no GD, no Imagick, no
`intervention/image`, no font file.

```php
Avatar::for('Ada Lovelace')->src();
// data:image/svg+xml;base64,PHN2ZyB4bWxucz…
```

Two properties worth knowing:

- **Resolution-independent.** One render serves a 24-pixel list row and a retina
  profile header, so `size` is a layout hint rather than a quality ceiling.
- **It is text.** It goes in a data URI, a database column or a file with no
  encoding step, and it compresses.

The output carries `role="img"` and an `aria-label` of the identity's label, so
a screen reader announces the person rather than "image".

Fonts are named by **family** (`sans-serif` by default, settable with
`fontFamily()`), not shipped. See
[architecture](../architecture.md#fonts-and-the-licences-in-them) for why that
matters more than it sounds.

## `gravatar-url`

Emits a link rather than drawing anything.

```php
Avatar::builder()->renderer(new GravatarUrlRenderer)->url('ada@example.com');
// https://gravatar.com/avatar/b5fc85e5…?s=100&r=g&d=mp
```

Supports `Format::Url` and nothing else — asking it for SVG returns false from
`supports()` rather than producing something wrong.

The scheme is `https` unless `withHttps(false)` says otherwise, regardless of
the incoming request's scheme.

## Raster output

PNG, JPEG and WebP need `intervention/image`:

```bash
composer require intervention/image
```

`isAvailable()` returns false without it, so the manager skips the renderer
instead of fataling on a missing class. `quality()` applies here and is ignored
by the SVG renderer, which has no lossy step.

## `Avatar` — what comes back

| Member | Answers |
|---|---|
| `isUrl()` / `isInline()` | Which kind of result this is |
| `src()` | Ready for an `<img src="">` — a URL, or a data URI |
| `bytes()` | The raw contents; throws for a URL result |
| `__toString()` | `src()` |

```php
$avatar = Avatar::for('Ada Lovelace');

$avatar->isInline();   // true
$avatar->src();        // data:image/svg+xml;base64,…
$avatar->bytes();      // <svg xmlns="http://www.w3.org/2000/svg" …
```

`bytes()` throwing for a URL result is deliberate: the alternative is fetching
it, and a data-access method that silently makes an HTTP request is how a page
render turns into a timeout.

## Registering your own

```php
Avatar::extendRenderer('avif', fn (): AvatarRenderer => new AvifRenderer);
```

A closure, not a class name — see [architecture](../architecture.md#the-registry-takes-a-closure).

---
[← Docs index](../../README.md#documentation)
