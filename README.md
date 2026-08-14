# laranail/avatar

[![Packagist](https://img.shields.io/packagist/v/laranail/avatar.svg?style=flat-square)](https://packagist.org/packages/laranail/avatar)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/avatar/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/avatar/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/avatar/static-analysis.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/avatar/actions/workflows/static-analysis.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

> Avatars for Laravel — Gravatar and locally-rendered initials behind one immutable fluent builder,
> with independent source and renderer seams and no image extension required.

Requires PHP `^8.4.1 || ^8.5` and Laravel `^13.0`.

## Install

```bash
composer require laranail/avatar
```

No GD, no Imagick, no `intervention/image` — the default renderer emits SVG, which is text. A CI job
disables both extensions and asserts initials still render.

## Quick start

```php
use Simtabi\Laranail\Avatar\Facades\Avatar;

Avatar::for('ada@example.com')->src();   // https://gravatar.com/avatar/b5fc85e5…?s=100&r=g&d=mp
Avatar::for('Ada Lovelace')->src();      // data:image/svg+xml;base64,…  ← drawn locally

Avatar::builder()->size(64)->rounded()->for($user);
```

Same call, two answers, because the **chain** decides: `['gravatar', 'initials']` means "a Gravatar
when there is an email address, otherwise draw the name" — and neither source contains a line about
the other.

## Two seams, not one

| Contract | Answers | Ships |
|---|---|---|
| `AvatarSource` | *Where the identity comes from* | `gravatar`, `initials` |
| `AvatarRenderer` | *How pixels get made* | `svg`, `gravatar-url` |

They vary independently: a Gravatar identity can fall back to locally-rendered initials, and an
initials identity can render as SVG or PNG. Collapsing them is what produced the 1,019-line
god-class this package replaces — the one where every new provider touched the same method as every
new output format.

A new provider is a `Sources/` entry. A new output format is a `Renderers/` entry.

## <a name="documentation"></a>Documentation

Hosted at **[opensource.simtabi.com/documentation/laranail/avatar](https://opensource.simtabi.com/documentation/laranail/avatar/)**.

### Guides
- [Installation](docs/installation.md) — requirements, what to publish, and why there are no routes
- [Getting started](docs/getting-started.md) — the chain, the seams, and the first calls
- [Configuration](docs/configuration.md) — every key and its environment variable
- [Architecture](docs/architecture.md) — the two seams, immutability, and the fonts that could not ship
- [Release](docs/release.md) — cutting a version

### Reference
- [Sources](docs/tools/sources.md) — the identity seam, and why the hash is SHA-256
- [Renderers](docs/tools/renderers.md) — the pixel seam, and what `Avatar` gives back
- [The builder](docs/tools/builder.md) — the full fluent API

### Recipes
- [Store an avatar](docs/recipes/store-an-avatar.md)
- [Add an avatar source](docs/recipes/add-a-source.md)

## Stability

Pre-1.0, with immutable tags — every release is its own `v0.1.x` and none is ever re-pointed.
Constraints resolve `^0.1`. New SemVer minors begin at 1.0.

Libravatar, UI-Avatars and DiceBear sources, and a raster renderer behind `intervention/image`, are
candidates for `v0.2` — all of them additions at one seam or the other, touching nothing else.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the
[Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
