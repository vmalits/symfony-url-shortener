<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Event;

final readonly class ShortUrlVisitedEvent
{
    public function __construct(
        public string $code,
        public string $ip,
        public ?string $userAgent,
        public ?string $referrer,
    ) {
    }
}
