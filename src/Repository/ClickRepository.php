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
}
