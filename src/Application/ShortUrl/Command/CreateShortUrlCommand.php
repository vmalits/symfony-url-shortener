<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\Command;

final readonly class CreateShortUrlCommand
{
    public function __construct(
        public string $originalUrl,
        public int $userId,
    ) {
    }
}
