<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\QueryHandler;

use App\Application\ShortUrl\Query\GetDashboardQuery;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class GetDashboardHandler
{
    public function __construct(
        private ShortUrlRepositoryInterface $repository,
        private ClickRepositoryInterface $clickRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return array{shortUrls: list<ShortUrl>, clickCounts: array<int, int>, countries: list<array<string, mixed>>, referrers: list<array<string, mixed>>, devices: list<array<string, mixed>>}
     */
    public function __invoke(GetDashboardQuery $query): array
    {
        $user = $this->userRepository->findById($query->userId);

        if (null === $user) {
            return ['shortUrls' => [], 'clickCounts' => [], 'countries' => [], 'referrers' => [], 'devices' => []];
        }

        $shortUrls = $this->repository->findByUser($user);
        $clickCounts = $this->clickRepository->countGroupedByShortUrl($shortUrls);
        $analytics = $this->clickRepository->getAggregatedAnalytics($shortUrls);

        return [
            'shortUrls' => $shortUrls,
            'clickCounts' => $clickCounts,
            'countries' => $analytics['countries'],
            'referrers' => $analytics['referrers'],
            'devices' => $analytics['devices'],
        ];
    }
}
