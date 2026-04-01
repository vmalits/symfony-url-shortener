<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrineShortUrlRepository implements ShortUrlRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findByCode(string $code): ?ShortUrl
    {
        return $this->em->getRepository(ShortUrl::class)->findOneBy(['code' => $code]);
    }

    public function findById(int $id): ?ShortUrl
    {
        return $this->em->find(ShortUrl::class, $id);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(ShortUrl::class)->findAll();
    }

    public function findByUser(User $user): array
    {
        return $this->em->getRepository(ShortUrl::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function createUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->em->getRepository(ShortUrl::class)
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC');
    }

    public function save(ShortUrl $shortUrl): void
    {
        $this->em->persist($shortUrl);
        $this->em->flush();
    }

    public function remove(ShortUrl $shortUrl): void
    {
        $this->em->remove($shortUrl);
        $this->em->flush();
    }
}
