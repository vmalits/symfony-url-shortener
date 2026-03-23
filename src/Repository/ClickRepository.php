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
}
