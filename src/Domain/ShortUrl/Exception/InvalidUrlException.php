<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Exception;

final class InvalidUrlException extends \DomainException
{
    public static function empty(): self
    {
        return new self('Please enter a URL.');
    }

    public static function invalid(string $url): self
    {
        return new self(sprintf('"%s" is not a valid URL.', $url));
    }
}
