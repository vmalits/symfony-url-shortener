<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Event;

final readonly class ShortUrlCreatedEvent
{
    public function __construct(
        public string $code,
        public string $originalUrl,
        public ?int $userId,
    ) {
    }
}
