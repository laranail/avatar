# Release

## Versioning

Semantic versioning with **immutable tags**. Every release gets its own
`v0.1.x`, its own changelog section and its own GitHub release; a tag, once
pushed, is never re-pointed.

A moving tag means two machines resolving `^0.1` on the same day can get
different code, and a `composer.lock` recording `v0.1.0` tells you nothing about
what was installed. Consumers still constrain on `^0.1`; what they get is a tag
that says which build it is. New SemVer minors begin at 1.0.

## The public surface

**Supported:** the `Avatar` facade, `AvatarManager`, `AvatarBuilder`, both
contracts, the `Data` value types, the two enums, the shipped adapters' class
names, and the published `config/laranail/avatar.php` keys.

**Internal, free to change:** the adapters' constructor signatures beyond their
documented arguments, and the exact SVG markup — its *content* (initials,
colours, `role`, `aria-label`) is supported; its byte-for-byte layout is not.

## Before tagging

```bash
composer lint         # parallel-lint, Pint, PHPStan, Rector
composer test         # Pest
composer validate --strict
composer audit
```

## The job that matters most

CI disables **GD and Imagick** and asserts initials still render. That is the
package's central claim — no image extension required — and nothing else proves
it: GitHub's runner has GD compiled in, so a renderer that quietly started
depending on it would pass every other job.

If that job is removed or skipped, the README is making a promise nobody is
checking.

## Cutting it

1. Update `CHANGELOG.md` under the version being cut. The release workflow
   extracts that section verbatim as the GitHub release body — every release
   carries a human-readable summary, never auto-generated notes alone.
2. Commit.
3. Tag `vX.Y.Z` and push the tag.

```bash
git tag -a v0.1.1 -m "…"
git push origin v0.1.1
```

## Before adding a font

**Read the licence out of the binary, not the filename.**

The package this was extracted from shipped three, and two could not lawfully be
there — a GPL-3.0 serif whose font exception does not cover redistributing the
file, and a "Microsoft YaHei" that was actually Droid Sans Fallback under an
Ascender EULA forbidding copying. Neither was visible from the filename; both
came out of the name table.

One font ships: `resources/fonts/Roboto-Bold.ttf`, Apache-2.0, which is
unambiguous. The SVG renderer does not use it — it names a font *family* the
host already has — so it is there for a future raster renderer and is currently
the only binary to audit. Keep the count that low if you can.

## Distribution

Not Packagist. laranail packages resolve inter-package dependencies through git
**VCS repositories**:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/laranail/avatar" }
]
```

Declare the full **transitive** `laranail/*` closure — Composer ignores a
dependency's own `repositories`, so the root package must list a `vcs` entry for
every laranail package it pulls, not only the direct ones.

---
[← Docs index](../README.md#documentation)
