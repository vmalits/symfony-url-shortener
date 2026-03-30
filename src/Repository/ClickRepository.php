<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Click;
use App\Entity\ShortUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Click>
 */
class ClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Click::class);
    }

    /**
     * @param array<ShortUrl> $shortUrls
     *
     * @return array<int, int>
     */
    public function countGroupedByShortUrl(array $shortUrls): array
    {
        if (empty($shortUrls)) {
            return [];
        }

        $result = $this->getEntityManager()
            ->createQuery('
                SELECT IDENTITY(c.shortUrl) AS shortUrlId, COUNT(c.id) AS count
                FROM App\Entity\Click c
                WHERE c.shortUrl IN (:urls)
                GROUP BY c.shortUrl
            ')
            ->setParameter('urls', $shortUrls)
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['shortUrlId']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function countByDay(int $days = 7): array
    {
        $startDate = new \DateTimeImmutable('-'.$days.' days');

        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT DATE(created_at) AS date, COUNT(*) AS count
            FROM clicks
            WHERE created_at >= :startDate
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ';

        $result = $conn->executeQuery($sql, ['startDate' => $startDate->format('Y-m-d H:i:s')]);

        $byDay = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $byDay[$row['date']] = (int) $row['count'];
        }

        return $byDay;
    }

    /**
     * @return array<int, array{country: string|null, cnt: int}>
     */
    public function getTopCountries(ShortUrl $shortUrl, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.country, COUNT(c.id) AS cnt')
            ->where('c.shortUrl = :url')
            ->setParameter('url', $shortUrl)
            ->groupBy('c.country')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{referrer: string|null, cnt: int}>
     */
    public function getTopReferrers(ShortUrl $shortUrl, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.referrer, COUNT(c.id) AS cnt')
            ->where('c.shortUrl = :url')
            ->setParameter('url', $shortUrl)
            ->groupBy('c.referrer')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTopDevices(ShortUrl $shortUrl, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT
                CASE
                    WHEN user_agent LIKE \'%Mobile%\' OR user_agent LIKE \'%Android%\' AND user_agent NOT LIKE \'%iPad%\' THEN \'Mobile\'
                    WHEN user_agent LIKE \'%iPad%\' THEN \'Tablet\'
                    WHEN user_agent LIKE \'%Tablet%\' THEN \'Tablet\'
                    WHEN user_agent IS NOT NULL AND user_agent != \'\' THEN \'Desktop\'
                    ELSE \'Unknown\'
                END AS device,
                COUNT(*) AS cnt
            FROM clicks
            WHERE short_urls_id = :urlId
            GROUP BY device
            ORDER BY cnt DESC
            LIMIT :limit
        ';

        $result = $conn->executeQuery($sql, [
            'urlId' => $shortUrl->getId(),
            'limit' => $limit,
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * @param array<ShortUrl> $shortUrls
     *
     * @return array{countries: list<array<string, mixed>>, referrers: list<array<string, mixed>>, devices: list<array<string, mixed>>}
     */
    public function getAggregatedAnalytics(array $shortUrls, int $limit = 10): array
    {
        if (empty($shortUrls)) {
            return ['countries' => [], 'referrers' => [], 'devices' => []];
        }

        $ids = array_map(fn (ShortUrl $url) => $url->getId(), $shortUrls);
        $conn = $this->getEntityManager()->getConnection();

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_values($ids);

        $countriesSql = "
            SELECT country, COUNT(*) AS cnt
            FROM clicks
            WHERE short_urls_id IN ($placeholders)
            GROUP BY country
            ORDER BY cnt DESC
            LIMIT ?
        ";
        $countries = $conn->executeQuery($countriesSql, [...$params, $limit])->fetchAllAssociative();

        $referrersSql = "
            SELECT referrer, COUNT(*) AS cnt
            FROM clicks
            WHERE short_urls_id IN ($placeholders)
            GROUP BY referrer
            ORDER BY cnt DESC
            LIMIT ?
        ";
        $referrers = $conn->executeQuery($referrersSql, [...$params, $limit])->fetchAllAssociative();

        $devicesSql = "
            SELECT
                CASE
                    WHEN user_agent LIKE '%Mobile%' OR (user_agent LIKE '%Android%' AND user_agent NOT LIKE '%iPad%') THEN 'Mobile'
                    WHEN user_agent LIKE '%iPad%' THEN 'Tablet'
                    WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                    WHEN user_agent IS NOT NULL AND user_agent != '' THEN 'Desktop'
                    ELSE 'Unknown'
                END AS device,
                COUNT(*) AS cnt
            FROM clicks
            WHERE short_urls_id IN ($placeholders)
            GROUP BY device
            ORDER BY cnt DESC
            LIMIT ?
        ";
        $devices = $conn->executeQuery($devicesSql, [...$params, $limit])->fetchAllAssociative();

        return ['countries' => $countries, 'referrers' => $referrers, 'devices' => $devices];
    }
}
