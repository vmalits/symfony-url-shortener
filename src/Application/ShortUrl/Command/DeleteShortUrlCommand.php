<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\Command;

final readonly class DeleteShortUrlCommand
{
    public function __construct(
        public int $shortUrlId,
        public int $userId,
    ) {
    }
}
