<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\Query;

final readonly class GetAnalyticsQuery
{
    public function __construct(
        public int $shortUrlId,
    ) {
    }
}
