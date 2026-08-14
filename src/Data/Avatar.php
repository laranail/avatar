<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Data;

use RuntimeException;
use Simtabi\Laranail\Avatar\Enums\Format;
use Stringable;

/**
 * A rendered avatar: either a URL to fetch, or bytes to serve.
 *
 * The distinction is carried rather than inferred. A caller handed a plain
 * string cannot tell whether it holds a link or an SVG document, and the two
 * need different handling in a template — one goes in `src`, the other is
 * echoed inline. Guessing by looking for `http` at the front is the sort of
 * check that works until someone renders a data URI.
 */
final readonly class Avatar implements Stringable
{
    private function __construct(
        public Format $format,
        public ?string $url,
        public ?string $contents,
        public Identity $identity,
    ) {}

    public static function url(string $url, Identity $identity, Format $format = Format::Url): self
    {
        return new self($format, $url, null, $identity);
    }

    public static function contents(string $contents, Identity $identity, Format $format): self
    {
        return new self($format, null, $contents, $identity);
    }

    public function isUrl(): bool
    {
        return $this->url !== null;
    }

    public function isInline(): bool
    {
        return $this->contents !== null;
    }

    /**
     * A `src` attribute value, whichever form this is.
     *
     * Bytes become a data URI, so a template can use one expression for both
     * without asking which it got.
     */
    public function src(): string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        return 'data:' . $this->format->mimeType() . ';base64,' . base64_encode((string) $this->contents);
    }

    /**
     * The raw bytes, fetching them if this is a URL.
     *
     * Deliberately **not** implemented here: a value object that makes a
     * network call when read is a value object that can time out, and callers
     * are better served by an explicit fetch they can see in a stack trace.
     * Inline renderers already carry their bytes.
     */
    public function bytes(): string
    {
        if ($this->contents !== null) {
            return $this->contents;
        }

        throw new RuntimeException(
            'This avatar is a URL, not bytes. Fetch it yourself if you need the image — this object '
            . 'does not make network calls, so that a template read cannot time out.',
        );
    }

    public function __toString(): string
    {
        return $this->src();
    }
}
