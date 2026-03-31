<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Exception;

final class ShortUrlNotFoundException extends \RuntimeException
{
    public static function byCode(string $code): self
    {
        return new self(sprintf('Short URL with code "%s" not found.', $code));
    }

    public static function byId(int $id): self
    {
        return new self(sprintf('Short URL with id "%d" not found.', $id));
    }
}
