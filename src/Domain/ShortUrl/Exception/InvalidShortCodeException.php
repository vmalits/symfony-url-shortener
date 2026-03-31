<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Exception;

final class InvalidShortCodeException extends \DomainException
{
    public static function empty(): self
    {
        return new self('Short code cannot be empty.');
    }

    public static function tooLong(string $code): self
    {
        return new self(sprintf('Short code "%s" is too long (max 10 characters).', $code));
    }
}
