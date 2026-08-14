# Sources

An `AvatarSource` answers **where the identity comes from**. Two ship.

```php
interface AvatarSource
{
    public function identify(mixed $subject): ?Identity;
    public function name(): string;
}
```

`null` means "not mine" and passes the subject to the next source in the chain.
That is the whole fallback mechanism, and it is why neither shipped source
contains a line about the other.

## `Identity`

What a source produces:

| Field | Meaning |
|---|---|
| `key` | A **stable** identifier — for caching, and for picking a colour |
| `label` | Human-readable, for initials |
| `email` | A validated address, when there is one |
| `url` | A resolved image URL, when there is one |

```php
$identity->hasUrl();
$identity->initials(count: 2, uppercase: true);   // 'AL'
```

`initials()` is multibyte-aware throughout. `substr()` on a UTF-8 name returns a
broken byte sequence rather than a letter, which renders as a replacement
character — so a name in any non-Latin script gets a mojibake avatar.

## `gravatar`

Identifies anything carrying an email address.

```php
$source = new GravatarSource(
    algorithm: 'sha256',     // or 'md5'
    defaultImage: 'mp',      // Gravatar's own fallback
    rating: 'g',
);
```

Note that `identify()` sets `email` and leaves `url` **null** — the URL is built
later, by `urlFor()` or the `gravatar-url` renderer, because it depends on the
size and the https setting, which are appearance concerns.

### The hash is SHA-256, not MD5

Gravatar has accepted SHA-256 since 2023 and documents it as preferred. The
module this replaces used MD5, and **an MD5 of an email address is not a privacy
measure**: the input space is small enough that commercial rainbow tables cover
most real addresses, so a page rendering `<img src="…/avatar/<md5>">` publishes
its users' email addresses to anyone who scrapes it.

SHA-256 is not a fix for that in principle — the input space is the same size —
but it is what the service asks for, it is not precomputed at anything like the
same scale, and there is no reason to keep the weaker one.

`md5` stays reachable for an application that stored the old hash and needs to
match it.

## `initials`

Accepts anything with a name and **never passes**, which is why it belongs at
the end of the chain: a chain ending there always resolves to something
drawable.

```php
(new InitialsSource)->identify('Ada Lovelace');
// key: 'Ada Lovelace', label: 'Ada Lovelace'
```

## Registering your own

```php
// A service provider's boot()
use Simtabi\Laranail\Avatar\Facades\Avatar;
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;

Avatar::extendSource('ldap', fn (): AvatarSource => new LdapSource(app(LdapClient::class)));
```

Then put it in the chain:

```php
'chain' => ['ldap', 'gravatar', 'initials'],
```

`extendSource()` takes a **closure, not a class name**, so registering a source
is a deliberate act in application code that a config edit cannot reach.

Libravatar, UI-Avatars and DiceBear are all `Sources/` entries — none of them
needs a renderer change, because a URL-bearing identity renders the same way
whoever produced it.

---
[← Docs index](../../README.md#documentation)
