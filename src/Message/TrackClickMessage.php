<?php

declare(strict_types=1);

namespace App\Message;

final class TrackClickMessage
{
    public function __construct(
        public int $shortUrlId,
        public string $ip,
        public ?string $userAgent,
    ) {
    }
}
