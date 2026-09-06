<?php

declare(strict_types=1);

use Simtabi\Laranail\Avatar\Data\Avatar;
use Simtabi\Laranail\Avatar\Enums\Shape;
use Simtabi\Laranail\Avatar\Enums\Format;
use Simtabi\Laranail\Avatar\AvatarBuilder;
use Simtabi\Laranail\Avatar\AvatarManager;
use Simtabi\Laranail\Avatar\Data\Identity;
use Simtabi\Laranail\Avatar\Data\Appearance;
use Simtabi\Laranail\Avatar\Contracts\AvatarSource;
use Simtabi\Laranail\Avatar\Contracts\AvatarRenderer;
use Simtabi\Laranail\Avatar\Adapters\Renderers\SvgRenderer;
use Simtabi\Laranail\Avatar\Adapters\Sources\GravatarSource;
use Simtabi\Laranail\Avatar\Adapters\Sources\InitialsSource;
use Simtabi\Laranail\Avatar\Adapters\Renderers\GravatarUrlRenderer;

function avatars(): AvatarManager
{
    return new AvatarManager;
}

// -----------------------------------------------------------------------
// Immutability — the reason this package exists in this shape
// -----------------------------------------------------------------------

it('never mutates the builder it was called on', function (): void {
    // The class this replaces held nineteen mutable properties with setters
    // returning $this, and was a container singleton — so two components
    // rendering avatars on one page disagreed about the size, and the second
    // one won.
    $base = avatars()->builder();
    $large = $base->size(512)->square();

    expect($large)->not->toBe($base)
        ->and($base->appearance()->size)->toBe(100)
        ->and($base->appearance()->shape)->toBe(Shape::Circle)
        ->and($large->appearance()->size)->toBe(512)
        ->and($large->appearance()->shape)->toBe(Shape::Square);
});

it('lets one partially-configured builder fan out', function (): void {
    $branded = avatars()->builder()->colours('#123456');

    expect($branded->size(32)->appearance()->size)->toBe(32)
        ->and($branded->size(256)->appearance()->size)->toBe(256)
        ->and($branded->appearance()->size)->toBe(100);
});

// -----------------------------------------------------------------------
// The source chain
// -----------------------------------------------------------------------

it('prefers an email over a name when both are present', function (): void {
    $subject = new class
    {
        public string $email = 'imani@simtabi.com';

        public string $name = 'Imani Manyara';
    };

    $identity = avatars()->builder()->identify($subject);

    expect($identity->email)->toBe('imani@simtabi.com')
        ->and($identity->label)->toBe('Imani Manyara');
});

it('falls through to initials when there is no email', function (): void {
    // The whole fallback policy is the chain order. Neither source contains a
    // line about the other.
    $identity = avatars()->builder()->identify('Imani Manyara');

    expect($identity->email)->toBeNull()
        ->and($identity->label)->toBe('Imani Manyara');
});

it('treats an invalid address as no address rather than hashing it', function (): void {
    // An invalid email still hashes to a URL, and Gravatar answers it with the
    // default image rather than a 404 — so a typo would become a silently
    // generic avatar instead of the person's initials.
    $identity = avatars()->builder()->identify('not-an-email');

    expect($identity->email)->toBeNull()
        ->and($identity->label)->toBe('not-an-email');
});

it('throws when nothing in the chain can identify the subject', function (): void {
    expect(fn (): Identity => avatars()->builder()->identify(''))
        ->toThrow(RuntimeException::class, 'No source could identify');
});

it('lets a custom source be registered with a closure', function (): void {
    $manager = avatars();

    $manager->extendSource('always', fn (): AvatarSource => new class implements AvatarSource
    {
        public function identify(mixed $subject): Identity
        {
            return new Identity(key: 'fixed', label: 'Fixed Source');
        }

        public function name(): string
        {
            return 'always';
        }
    });

    $manager->chain(['always']);

    expect($manager->builder()->identify(null)->label)->toBe('Fixed Source')
        ->and($manager->availableSources())->toContain('always');
});

it('names the registered sources when the chain holds an unknown one', function (): void {
    expect(fn (): AvatarBuilder => avatars()->chain(['nope'])->builder())
        ->toThrow(RuntimeException::class, 'Unknown avatar source [nope]');
});

// -----------------------------------------------------------------------
// Initials
// -----------------------------------------------------------------------

it('takes one initial per word', function (): void {
    expect(avatars()->builder()->identify('Imani Manyara')->initials())->toBe('IM');
});

it('takes several characters from a mononym', function (): void {
    // A single letter in a circle reads worse than two.
    expect(avatars()->builder()->identify('Prince')->initials())->toBe('PR');
});

it('is multibyte-correct', function (): void {
    // substr() on a UTF-8 name returns a broken byte sequence, which renders as
    // a replacement character — so a name in any non-Latin script would get a
    // mojibake avatar.
    expect(avatars()->builder()->identify('李小龍')->initials())->toBe('李小')
        ->and(avatars()->builder()->identify('Ólafur Arnalds')->initials())->toBe('ÓA')
        ->and(avatars()->builder()->identify('Ελένη Παπαδοπούλου')->initials())->toBe('ΕΠ');
});

it('honours the initial count', function (): void {
    expect(avatars()->builder()->initials(3)->identify('Ada Byron Lovelace')->initials(3))->toBe('ABL');
});

// -----------------------------------------------------------------------
// Rendering
// -----------------------------------------------------------------------

it('renders initials as an SVG that needs no image extension', function (): void {
    $avatar = avatars()->for('Imani Manyara');

    expect($avatar->isInline())->toBeTrue()
        ->and($avatar->format)->toBe(Format::Svg)
        ->and($avatar->bytes())->toStartWith('<svg')
        ->and($avatar->bytes())->toContain('IM');
});

it('escapes what it puts in the markup', function (): void {
    // Initials come from user-supplied names and the SVG is echoed inline, so
    // this is an XSS surface — {!! !!} is exactly how an inline SVG reaches a
    // page.
    $svg = avatars()->for('<script>alert(1)</script> Bad')->bytes();

    expect($svg)->not->toContain('<script>')
        ->and($svg)->toContain('&lt;');
});

it('refuses a colour that is not one', function (): void {
    // A colour goes into an SVG attribute unquoted by any templating layer, so
    // this would be markup rather than a colour.
    $svg = avatars()->builder()->colours('#fff" onload="alert(1)')->for('AB')->bytes();

    expect($svg)->not->toContain('onload');
});

it('gives the same person the same colour every time', function (): void {
    // An avatar that changes colour when the cache expires reads as a different
    // person.
    $first = avatars()->for('Imani Manyara')->bytes();
    $second = avatars()->for('Imani Manyara')->bytes();

    expect($first)->toBe($second);
});

it('gives different people different colours', function (): void {
    $a = avatars()->for('Aaaa')->bytes();
    $b = avatars()->for('Zzzz')->bytes();

    expect($a)->not->toBe($b);
});

it('renders a data uri that a template can use directly', function (): void {
    expect(avatars()->for('AB')->src())->toStartWith('data:image/svg+xml;base64,');
});

it('renders the requested shape', function (): void {
    expect(avatars()->builder()->circle()->for('AB')->bytes())->toContain('<circle')
        ->and(avatars()->builder()->square()->for('AB')->bytes())->toContain('<rect');
});

// -----------------------------------------------------------------------
// Gravatar
// -----------------------------------------------------------------------

it('hashes an email with SHA-256 rather than MD5', function (): void {
    // MD5 of an email is not a privacy measure: commercial rainbow tables cover
    // most real addresses, so a page rendering <img src=".../avatar/<md5>">
    // publishes its users' addresses to anyone who scrapes it. Gravatar has
    // accepted SHA-256 since 2023 and documents it as preferred.
    $hash = new GravatarSource()->hash('imani@simtabi.com');

    expect($hash)->toHaveLength(64)
        ->and($hash)->toBe(hash('sha256', 'imani@simtabi.com'))
        ->and($hash)->not->toBe(md5('imani@simtabi.com'));
});

it('normalises the address before hashing, as gravatar documents', function (): void {
    // The service hashes the normalised form, so hashing the raw input produces
    // a URL that resolves to nobody — silently, since Gravatar answers with the
    // default image rather than a 404.
    $source = new GravatarSource;

    expect($source->hash('  IMANI@Simtabi.COM  '))->toBe($source->hash('imani@simtabi.com'));
});

it('keeps md5 reachable for an application that stored the old hash', function (): void {
    expect(new GravatarSource(algorithm: 'md5')->hash('imani@simtabi.com'))
        ->toBe(md5('imani@simtabi.com'));
});

it('emits https by default', function (): void {
    // The two modules merged here disagreed — one defaulted true, two of the
    // other's methods defaulted false — so one application emitted both, and
    // the http one is a mixed-content warning on every page that shows it.
    $url = avatars()->builder('gravatar-url')->for('imani@simtabi.com')->src();

    expect($url)->toStartWith('https://');
});

it('drops to http only when explicitly asked', function (): void {
    $url = avatars()->builder('gravatar-url')->withHttps(false)->for('imani@simtabi.com')->src();

    expect($url)->toStartWith('http://');
});

it('carries the requested size into the url', function (): void {
    expect(avatars()->builder('gravatar-url')->size(256)->for('imani@simtabi.com')->src())
        ->toContain('s=256');
});

it('makes no network call to decide whether an address has an image', function (): void {
    // Whether it does is a question only Gravatar can answer, and answering it
    // here would mean an HTTP request inside a template render.
    $before = microtime(true);
    avatars()->builder('gravatar-url')->for('nobody@example.invalid');

    expect(microtime(true) - $before)->toBeLessThan(0.5);
});

it('says what to do when a url renderer gets an identity with no email', function (): void {
    $builder = avatars()->builder('gravatar-url')->sources([new InitialsSource]);

    expect(fn (): Avatar => $builder->for('Imani Manyara'))
        ->toThrow(RuntimeException::class, 'initials source');
});

// -----------------------------------------------------------------------
// Renderers
// -----------------------------------------------------------------------

it('reports what each renderer supports', function (): void {
    expect(new SvgRenderer()->supports(Format::Svg))->toBeTrue()
        ->and(new SvgRenderer()->supports(Format::Png))->toBeFalse()
        ->and(new GravatarUrlRenderer()->supports(Format::Url))->toBeTrue();
});

it('needs nothing to render svg', function (): void {
    // The property that lets intervention/image be a suggest.
    expect(new SvgRenderer()->isAvailable())->toBeTrue()
        ->and(Format::Svg->needsRasteriser())->toBeFalse()
        ->and(Format::Png->needsRasteriser())->toBeTrue();
});

it('names the registered renderers when asked for an unknown one', function (): void {
    expect(fn (): AvatarRenderer => avatars()->renderer('nope'))
        ->toThrow(RuntimeException::class, 'Unknown avatar renderer [nope]');
});

it('refuses to hand back a url as bytes', function (): void {
    // A value object that fetches when read is one that can time out.
    expect(fn (): string => avatars()->builder('gravatar-url')->for('imani@simtabi.com')->bytes())
        ->toThrow(RuntimeException::class, 'does not make network calls');
});

// -----------------------------------------------------------------------
// Contrast
// -----------------------------------------------------------------------

it('picks text colour by luminance rather than always white', function (string $background, string $expected): void {
    // White initials on a light background wash out at small sizes, which is
    // where avatars mostly live. The default palette is dark enough that this
    // is a no-op for it — it matters for a custom one.
    $appearance = Appearance::default()->withColours($background);

    expect($appearance->foregroundFor(new Identity(key: 'x')))->toBe($expected);
})->with([
    'white'      => ['#ffffff', '#1f2933'],
    'near white' => ['#f5f5f5', '#1f2933'],
    'yellow'     => ['#ffeb3b', '#1f2933'],
    'green'      => ['#00ff00', '#1f2933'],
    'blue'       => ['#0000ff', '#ffffff'],
    'black'      => ['#000000', '#ffffff'],
    'shorthand'  => ['#fff', '#1f2933'],
]);

it('applies sRGB gamma rather than averaging the channels', function (): void {
    // A naive (r+g+b)/3 calls #0000ff mid-bright when it is nearly black to the
    // eye, and would put dark text on it.
    expect(Appearance::default()->withColours('#0000ff')->foregroundFor(new Identity(key: 'x')))
        ->toBe('#ffffff');
});

it('assumes dark for a colour it cannot read', function (): void {
    // White text is the safer default, since most palettes are dark.
    expect(Appearance::default()->withColours('not-a-colour')->foregroundFor(new Identity(key: 'x')))
        ->toBe('#ffffff');
});

it('keeps every default palette entry readable with white text', function (): void {
    foreach (Appearance::DEFAULT_PALETTE as $colour) {
        expect(Appearance::default()->withColours($colour)->foregroundFor(new Identity(key: 'x')))
            ->toBe('#ffffff', "the shipped palette entry {$colour} is too light for white text");
    }
});
