<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\ValueObject;

use App\Domain\ShortUrl\Exception\InvalidShortCodeException;

final readonly class ShortCode implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ('' === $this->value) {
            throw InvalidShortCodeException::empty();
        }

        if (\strlen($this->value) > 10) {
            throw InvalidShortCodeException::tooLong($this->value);
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
