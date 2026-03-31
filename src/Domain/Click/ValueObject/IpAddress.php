<?php

declare(strict_types=1);

namespace App\Domain\Click\ValueObject;

final readonly class IpAddress implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ('' !== $this->value && false === filter_var($this->value, \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid IP address.', $this->value));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
