# Store an avatar

The package registers **no routes** — rendering images on a public endpoint is a
CPU-exhaustion vector. Render once, store it, and serve it however you serve
other files.

## Render and write

```php
use Illuminate\Support\Facades\Storage;
use Simtabi\Laranail\Avatar\Facades\Avatar;

$avatar = Avatar::builder()->size(256)->for($user);

if ($avatar->isInline()) {
    $path = "avatars/{$user->id}.svg";
    Storage::disk('public')->put($path, $avatar->bytes());
    $user->update(['avatar_path' => $path]);
}
```

Guard on `isInline()`. A `gravatar-url` result has no bytes to write — `bytes()`
throws rather than fetching, deliberately, so that a template read cannot turn
into a network timeout.

## On creation

```php
class User extends Model
{
    protected static function booted(): void
    {
        static::created(function (self $user): void {
            StoreAvatar::dispatch($user);
        });
    }
}
```

A queued job rather than inline: an avatar is not worth adding latency to a
signup, and a failure should retry rather than fail the registration.

## Serve it

```blade
<img src="{{ $user->avatar_path
    ? Storage::disk('public')->url($user->avatar_path)
    : Avatar::for($user)->src() }}"
     alt="{{ $user->name }}">
```

The fallback renders on the fly, which is fine for SVG — it is a few hundred
bytes of string building with no image library involved.

## Or do not store it at all

For SVG, storing is often not worth it:

```blade
<img src="{{ Avatar::for($user)->src() }}" alt="{{ $user->name }}">
```

That is a data URI built in memory. It costs no disk, no route, no cache
invalidation when a name changes — and because the colour is derived from a hash
of the identity's key, it is stable across requests and servers without anything
being persisted.

Store when you need a stable public URL (an email, an OG image, a third party
fetching it). Otherwise render.

---
[← Docs index](../../README.md#documentation)
