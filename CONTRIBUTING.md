# Contributing

Thanks for helping improve `laranail/avatar`.

## Getting set up

```bash
composer install
composer test
composer lint
```

Requires PHP `^8.4.1 || ^8.5`. **No image extension is needed** — the default
renderer emits SVG, which is text. `intervention/image` is a `suggest` and only
the raster renderer wants it.

## What must pass

- **Style** — `composer pint-fix` (Laravel Pint preset, `declare(strict_types=1)`).
- **Static analysis** — `composer phpstan`.
- **Rector** — `composer rector` (dry run), pinned to the **`php84`** set. Not
  `php85`: this package supports 8.4, and the newer set would rewrite code into
  syntax that parses on one CI job and fails on the other.
- **Tests** — `composer test` (Pest). Add tests for new behaviour.

## The two seams

This package has **two** extension points and they are deliberately separate:

| Contract | Answers |
|---|---|
| `Contracts\AvatarSource` | *Where the identity comes from* |
| `Contracts\AvatarRenderer` | *How pixels get made* |

They vary independently — a Gravatar identity can fall back to locally-rendered
initials, and an initials identity can render as SVG or PNG. Collapsing them is
what produced the 1,019-line god-class this package replaces.

**A new provider is a `Sources/` entry. A new output format is a `Renderers/`
entry.** If a change needs to touch both, that is worth a comment explaining
why, because it usually means the seam is in the wrong place.

## The rules that are not style

- **Everything is immutable.** `AvatarBuilder` and `Appearance` are
  clone-on-write: every setter returns a new instance. A shared avatar service
  that mutates is a bug waiting for a second caller, and the merged class this
  replaces mutated `$this`.
- **`https` defaults to `true`, everywhere.** The two modules this package came
  from disagreed — Gravatar defaulted true and two Avatar bridge methods
  defaulted false — so the same application emitted both. `withHttps(false)` is
  the only way down and is named for what it does.
- **A renderer declares what it supports.** `supports(Format)` and
  `isAvailable()` are how the manager decides, so a renderer cannot claim a
  format it has not implemented and an absent extension is a configuration
  state rather than a fatal.
- **No routes.** Rendering images on a public endpoint is a CPU-exhaustion
  vector nobody asked for. `store()` plus the host's own route covers every real
  case, and a package that registers one has made that decision for its users.

## Fonts

**Do not add a font binary to this repository without reading its licence out of
the file itself.**

The package this was extracted from shipped three, and two could not lawfully be
there. `FreeSerif.ttf` is GPL-3.0 — its font exception covers documents that
embed the font, not redistribution of the file, so shipping it inside an MIT
package distributed GPL software under an MIT banner. `msyh.ttf` was not
Microsoft YaHei despite the filename; the family is Droid Sans Fallback and the
embedded licence is an Ascender EULA reading "you may not copy this font
software".

Neither was visible from the filename. Both came out of the name table in the
binary. Only `Roboto-Bold.ttf` (Apache-2.0) is left, and the SVG renderer does
not even use it — it names a family the host already has. Prefer that.

## Tests

Pest, under `tests/`. Two properties are worth asserting explicitly whenever you
touch the relevant code, because both have regressed before:

```php
// A setter returns a new instance
expect($builder->size(64))->not->toBe($builder);

// Generated URLs are https regardless of the incoming request scheme
expect($url)->toStartWith('https://');
```

## Commits and PRs

Subject ≤ 72 characters, imperative mood. The body explains *why*, not *what*.
No AI attribution.

---

Report vulnerabilities per [SECURITY.md](SECURITY.md) rather than in an issue.
