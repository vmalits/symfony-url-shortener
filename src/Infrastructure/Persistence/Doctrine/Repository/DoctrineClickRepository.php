<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Click\Entity\Click;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\ShortUrl\Entity\ShortUrl;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineClickRepository implements ClickRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(Click $click): void
    {
        $this->em->persist($click);
        $this->em->flush();
    }

    public function countGroupedByShortUrl(array $shortUrls): array
    {
        if (empty($shortUrls)) {
            return [];
        }

        $result = $this->em->createQuery('
            SELECT IDENTITY(c.shortUrl) AS shortUrlId, COUNT(c.id) AS count
            FROM App\Domain\Click\Entity\Click c
            WHERE c.shortUrl IN (:urls)
            GROUP BY c.shortUrl
        ')->setParameter('urls', $shortUrls)->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['shortUrlId']] = (int) $row['count'];
        }

        return $counts;
    }

    public function countByDay(int $days = 7): array
    {
        $startDate = new \DateTimeImmutable('-'.$days.' days');
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery(
            'SELECT DATE(created_at) AS date, COUNT(*) AS count FROM clicks WHERE created_at >= :startDate GROUP BY DATE(created_at) ORDER BY date ASC',
            ['startDate' => $startDate->format('Y-m-d H:i:s')],
        );

        $byDay = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $byDay[$row['date']] = (int) $row['count'];
        }

        return $byDay;
    }

    public function getAggregatedAnalytics(array $shortUrls, int $limit = 10): array
    {
        if (empty($shortUrls)) {
            return ['countries' => [], 'referrers' => [], 'devices' => []];
        }

        $ids = array_map(static fn (ShortUrl $url): ?int => $url->getId(), $shortUrls);
        $conn = $this->em->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;

        $countries = $conn->executeQuery(
            "SELECT country, COUNT(*) AS cnt FROM clicks WHERE short_urls_id IN ($placeholders) GROUP BY country ORDER BY cnt DESC LIMIT ?",
            [...$params, $limit],
        )->fetchAllAssociative();

        $referrers = $conn->executeQuery(
            "SELECT referrer, COUNT(*) AS cnt FROM clicks WHERE short_urls_id IN ($placeholders) GROUP BY referrer ORDER BY cnt DESC LIMIT ?",
            [...$params, $limit],
        )->fetchAllAssociative();

        $devices = $conn->executeQuery(
            "SELECT CASE
                WHEN user_agent LIKE '%Mobile%' OR (user_agent LIKE '%Android%' AND user_agent NOT LIKE '%iPad%') THEN 'Mobile'
                WHEN user_agent LIKE '%iPad%' THEN 'Tablet'
                WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                WHEN user_agent IS NOT NULL AND user_agent != '' THEN 'Desktop'
                ELSE 'Unknown'
            END AS device, COUNT(*) AS cnt
            FROM clicks WHERE short_urls_id IN ($placeholders)
            GROUP BY device ORDER BY cnt DESC LIMIT ?",
            [...$params, $limit],
        )->fetchAllAssociative();

        return ['countries' => $countries, 'referrers' => $referrers, 'devices' => $devices];
    }
}
