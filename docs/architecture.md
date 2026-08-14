# Architecture

## Two seams, not one

```
src/Contracts/
  AvatarSource.php     ← where the identity comes from
  AvatarRenderer.php   ← how pixels get made

src/Adapters/
  Sources/{Gravatar,Initials}Source.php
  Renderers/{Svg,GravatarUrl}Renderer.php

src/AvatarManager.php  the registry — extendSource(), extendRenderer()
src/AvatarBuilder.php  immutable, clone-on-write
src/Data/              Identity, Appearance, Avatar
src/Enums/             Shape, Format
```

Source and renderer vary independently: a Gravatar identity can fall back to
locally-rendered initials, and an initials identity can render as SVG or PNG.

**Collapsing them is what produced the 1,019-line god-class this package
replaces.** That class knew about email hashing, palette selection, font
loading, GD, and the fallback order all at once, so every new provider touched
the same method as every new output format, and the four copies of the Gravatar
chain inside it had drifted apart.

Here, a new provider is a `Sources/` entry and a new output format is a
`Renderers/` entry. If a change needs both, the seam is in the wrong place.

## Capability is a method, not a claim

```php
interface AvatarRenderer
{
    public function supports(Format $format): bool;
    public function isAvailable(): bool;
    public function render(Identity $identity, Appearance $appearance): Avatar;
    public function name(): string;
}
```

`supports()` and `isAvailable()` are how the manager decides what to call, so a
renderer cannot claim a format it has not implemented, and a missing extension
is a **configuration state** rather than a fatal three frames deep.

## Immutable, because it is shared

`AvatarBuilder` and `Appearance` are clone-on-write — every setter returns a new
instance.

The merged class this replaces mutated `$this`. That is fine for exactly one
caller and wrong the moment a second one holds the same instance: a Blade
component setting `size(32)` for a list row silently resized the profile header
rendered later in the same request.

## The registry takes a closure

```php
Avatar::extendSource('ldap', fn (): AvatarSource => new LdapSource($client));
Avatar::extendRenderer('avif', fn (): AvatarRenderer => new AvifRenderer);
```

Deliberately **not** `Illuminate\Support\Manager`, which resolves the driver
named `foo` by calling `createFooDriver()` — interpolating a config value into a
method name. Config comes from a file an operator edits, and in a multi-tenant
install from a database row; it must never be able to become a method name.

## `https` is the default and downgrading is explicit

Generated URLs are `https` regardless of the incoming request's scheme.
`withHttps(false)` exists, is named for what it does, and is the only way down.

This was a real inconsistency rather than a hypothetical: `GravatarService`
defaulted `https = true`, while `AvatarService::getGravatar()` and
`getGravatarForEmail()` defaulted false. The same application emitted both.

## No routes, and that is a security decision

Rendering images on a public endpoint is a CPU-exhaustion vector. An
unauthenticated caller varying `?size=` walks straight into your image library,
and a package that registers such a route has made that choice for every
application that installs it.

`store()` plus the host's own route covers every real case, and leaves the
question of who may request an image where it belongs.

## Fonts, and the licences in them

The package this was extracted from shipped three font binaries, and two could
not lawfully be there:

- **`FreeSerif.ttf`** is GPL-3.0. Its font exception covers documents that
  *embed* the font, not redistribution of the file — so shipping it inside an
  MIT package distributed GPL software under an MIT banner.
- **`msyh.ttf`** was not Microsoft YaHei despite the filename. The family is
  Droid Sans Fallback and the embedded licence is an Ascender EULA reading "you
  may not copy this font software", licensed for up to five personal computers.

Neither was visible from the filename; both came out of the name table in the
binary. Only `Roboto-Bold.ttf` (Apache-2.0) survived the extraction, which took
the bundled fonts from 6.4 MB to 168 KB.

The SVG renderer needs none of them — it names a font *family* the host already
has — so the one that ships is there for a future raster renderer.

---
[← Docs index](../README.md#documentation)
