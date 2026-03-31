<?php

declare(strict_types=1);

namespace App\Application\Click\Command;

final readonly class TrackClickCommand
{
    public function __construct(
        public string $code,
        public string $ip,
        public ?string $userAgent,
        public ?string $referrer,
    ) {
    }
}
