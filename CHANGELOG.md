# Changelog

All notable changes to `laranail/avatar` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-08-14

### Added

Initial release. Extracted from `laranail/toolkit`'s `Modules\Avatar` and `Modules\Gravatar`, merged into one package.

- **Two seams, not one.** `Contracts\AvatarSource` answers *who*; `Contracts\AvatarRenderer` answers
  *how*. They vary independently — a Gravatar identity can fall back to locally-rendered initials,
  and an initials identity can render as SVG or PNG — and collapsing them is what produced the
  1,019-line class this replaces. A new provider is a `Sources/` entry; a new output format is a
  `Renderers/` entry.

- **The builder is immutable.** The original held nineteen mutable properties with setters returning
  `$this`, and was a container singleton, so two components rendering avatars on one page disagreed
  about the size and the second one won. Every method returns a new instance.

- **SVG is the default renderer**, so `intervention/image` is a `suggest`: no GD, no Imagick, no
  font file, and the output is resolution-independent.

### Fixed

- **Gravatar hashes are SHA-256, not MD5.** An MD5 of an email address is not a privacy measure —
  commercial rainbow tables cover most real addresses, so a page rendering
  `<img src=".../avatar/<md5>">` publishes its users' addresses to anyone who scrapes it. Gravatar
  has accepted SHA-256 since 2023 and documents it as preferred. `md5` stays reachable for an
  application that stored the old hash.

- **`https` is the default everywhere.** The two merged modules disagreed: `GravatarService`
  defaulted `https = true` while `AvatarService::getGravatar()` and `getGravatarForEmail()`
  defaulted it `false` — so one application emitted both, and the `http` one is a mixed-content
  warning on every page that shows it. `withHttps(false)` is the only way down.

- **Initials are multibyte-correct.** `substr()` on a UTF-8 name returns a broken byte sequence that
  renders as a replacement character, so a name in any non-Latin script got a mojibake avatar.

- **The SVG output is escaped.** Initials come from user-supplied names and the markup is echoed
  inline, so this is an XSS surface. Colours are pattern-matched rather than escaped, because a
  colour lands in an attribute no templating layer quotes.

- **Text colour is chosen by relative luminance** rather than always being white, so a light custom
  palette does not produce invisible initials.

### Removed

- **Two of the three bundled fonts.** `FreeSerif.ttf` is GPL-3.0 — its font exception covers
  documents that *embed* the font, not redistribution of the file — and shipping it inside an MIT
  package distributes GPL software under an MIT banner. `msyh.ttf` is worse: despite the filename it
  is not Microsoft YaHei but Droid Sans Fallback, carrying an Ascender Corporation EULA whose text
  reads *"you may not copy this font software"*.

  Only `Roboto-Bold.ttf` (Apache-2.0) ships. That takes the bundled fonts from 6.4 MB to 168 KB, and
  the SVG renderer needs none of them.
