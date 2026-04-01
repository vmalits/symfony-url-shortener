<?php

declare(strict_types=1);

namespace App\Application\Click\Command;

use App\Domain\Click\ValueObject\IpAddress;

final readonly class TrackClickCommand
{
    public function __construct(
        public string $code,
        public IpAddress $ip,
        public ?string $userAgent,
        public ?string $referrer,
    ) {
    }
}
