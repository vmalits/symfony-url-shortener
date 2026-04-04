<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\ValueObject;

use App\Domain\ShortUrl\Exception\InvalidUrlException;

final readonly class Url implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ('' === $this->value) {
            throw InvalidUrlException::empty();
        }

        if (false === filter_var($this->value, \FILTER_VALIDATE_URL)) {
            throw InvalidUrlException::invalid($this->value);
        }

        $scheme = parse_url($this->value, \PHP_URL_SCHEME);
        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw InvalidUrlException::unsafeScheme($this->value);
        }

        if (null !== parse_url($this->value, \PHP_URL_USER)) {
            throw InvalidUrlException::embeddedCredentials($this->value);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
