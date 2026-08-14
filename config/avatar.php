<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Source chain
    |--------------------------------------------------------------------------
    |
    | Read as `config('laranail.avatar.*')`; this file publishes to
    | `config/laranail/avatar.php`.
    |
    | Sources are tried in order until one identifies the subject, and that
    | ordering IS the fallback policy: `['gravatar', 'initials']` means "a
    | Gravatar when there is an email address, otherwise draw the name". Neither
    | source contains a line about the other, which is what lets you reorder
    | them, drop one, or register your own without touching either.
    |
    | End the chain with `initials` — it accepts anything with a name and never
    | passes, so a chain ending there always resolves to something drawable.
    |
    */

    'chain' => ['gravatar', 'initials'],

    /*
    |--------------------------------------------------------------------------
    | Renderer
    |--------------------------------------------------------------------------
    |
    | `svg` is the default and needs nothing — no GD, no Imagick, no
    | intervention/image, and no font file. It is also resolution-independent,
    | so one render serves a 24-pixel list row and a retina profile header.
    |
    | `gravatar-url` emits a Gravatar link instead of drawing anything.
    |
    */

    'renderer' => env('AVATAR_RENDERER', 'svg'),

    'format' => env('AVATAR_FORMAT', 'svg'),

    /*
    |--------------------------------------------------------------------------
    | Appearance
    |--------------------------------------------------------------------------
    |
    | Defaults only. Every one of them is overridable per call on the builder,
    | which returns a new instance each time — so a component that wants 32px
    | circles cannot change what the rest of the page renders.
    |
    | The palette is chosen for contrast against white text. Random hex would
    | eventually produce near-white, and white initials on near-white is an
    | invisible avatar.
    |
    */

    'size' => (int) env('AVATAR_SIZE', 100),

    'shape' => env('AVATAR_SHAPE', 'circle'),

    'palette' => null,

    /*
    |--------------------------------------------------------------------------
    | HTTPS
    |--------------------------------------------------------------------------
    |
    | Generated URLs are https regardless of the incoming request scheme, so an
    | avatar on a page served over http does not become a mixed-content warning
    | for everyone else.
    |
    | Set false only if you genuinely need plain http. The two modules this
    | package merges disagreed on this — one defaulted true, two methods of the
    | other defaulted false — so one application emitted both.
    |
    */

    'https' => env('AVATAR_HTTPS', true),

];
