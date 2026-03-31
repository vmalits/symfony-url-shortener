<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

final readonly class Email implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ('' === $this->value || false === filter_var($this->value, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid email address.', $this->value));
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
