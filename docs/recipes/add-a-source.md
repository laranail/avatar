# Add an avatar source

A new provider — LDAP, an internal photo service, Libravatar, UI-Avatars — is a
**source**, not a renderer. It answers *where the identity comes from*; how it
gets drawn is a separate question and stays separate.

## Implement the contract

```php
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;
use Simtabi\Laranail\Avatar\Data\Identity;

final readonly class LdapSource implements AvatarSource
{
    public function __construct(private LdapClient $client) {}

    public function identify(mixed $subject): ?Identity
    {
        $email = $subject instanceof User ? $subject->email : null;

        if ($email === null) {
            return null;      // not mine — the chain moves on
        }

        $photo = $this->client->photoUrlFor($email);

        return $photo === null ? null : new Identity(
            key: $email,
            label: $subject->name,
            email: $email,
            url: $photo,
        );
    }

    public function name(): string
    {
        return 'ldap';
    }
}
```

Two things carry the weight:

- **`null` means "not mine".** It is the whole fallback mechanism. Returning a
  half-filled `Identity` instead would stop the chain and render a blank.
- **`key` must be stable.** It is what the palette hashes to pick a colour, so
  one person keeps one colour across requests and servers. A key that varies —
  a timestamp, a request id — makes avatars flicker.

## Register it

```php
// A service provider's boot()
Avatar::extendSource('ldap', fn (): AvatarSource => new LdapSource(app(LdapClient::class)));
```

A closure, not a class name: registering a source is a deliberate act in
application code that a config edit cannot reach.

## Put it in the chain

```php
// config/laranail/avatar.php
'chain' => ['ldap', 'gravatar', 'initials'],
```

Order is the fallback policy. Keep `initials` last — it accepts anything with a
name and never passes, so the chain always resolves to something drawable.

Per-call, without touching config:

```php
Avatar::builder()->preferring(new LdapSource($client))->for($user);
```

## What you do not have to do

Nothing about rendering. A URL-bearing identity renders the same way whoever
produced it, so an LDAP photo, a Gravatar and a Libravatar all reach the same
renderer with no change to it — which is the point of the seam being where it
is.

---
[← Docs index](../../README.md#documentation)
