<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Exception;

final class ShortCodeGenerationFailedException extends \RuntimeException
{
    public static function maxRetriesExceeded(int $retries): self
    {
        return new self(sprintf('Failed to generate a unique short code after %d attempts.', $retries));
    }
}
