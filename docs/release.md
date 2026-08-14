# Release

## Versioning

Pre-1.0, this package follows the laranail convention: **one tag per line, and
it moves.** `v0.1.0` is re-pointed at `main` on every release, and consumers on
`^0.1` resolve whatever it currently points at.

That is not a preference, it is the invariant the whole family depends on.
`^0.1` on a `0.x` package means `>=0.1.0 <0.2.0`, so a tag left behind does not
ship consumers older *features* — it ships them code without the *fixes*, while
the release page looks perfectly healthy. `laranail/enumerator` sat two commits
behind its tag with nine packages depending on it, and the missing commits were
a preset and an ordering bugfix.

`scripts/verify-tag-currency.sh` enforces it, weekly and on demand: every tag
must be an ancestor of `main`, and the highest tag on the line named by
`extra.branch-alias` must be `main` itself.

**The cost, stated plainly:** a moving tag means two machines resolving `^0.1`
on different days can get different code, and a `composer.lock` recording
`v0.1.0` says less than it appears to. That is the price of the convention
while pre-1.0, and it is why `1.0` ends it — from then tags are immutable and
every release is its own version.

A package that outgrows the single moving tag cuts real SemVer versions instead;
`laranail/db-tools` did that at `0.7`, and `extra.branch-alias` is what declares
which line is live.

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
