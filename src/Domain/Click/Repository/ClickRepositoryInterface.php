<?php

declare(strict_types=1);

namespace App\Domain\Click\Repository;

use App\Domain\Click\Entity\Click;
use App\Domain\ShortUrl\Entity\ShortUrl;

interface ClickRepositoryInterface
{
    public function save(Click $click): void;

    /**
     * @param list<ShortUrl> $shortUrls
     *
     * @return array<int, int>
     */
    public function countGroupedByShortUrl(array $shortUrls): array;

    /**
     * @return array<string, int>
     */
    public function countByDay(int $days = 7): array;

    /**
     * @param list<ShortUrl> $shortUrls
     *
     * @return array{countries: list<array<string, mixed>>, referrers: list<array<string, mixed>>, devices: list<array<string, mixed>>}
     */
    public function getAggregatedAnalytics(array $shortUrls, int $limit = 10): array;
}
