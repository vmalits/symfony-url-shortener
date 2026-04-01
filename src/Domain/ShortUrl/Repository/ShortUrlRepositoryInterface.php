<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Repository;

use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\User\Entity\User;
use Doctrine\ORM\QueryBuilder;

interface ShortUrlRepositoryInterface
{
    public function findByCode(string $code): ?ShortUrl;

    public function findById(int $id): ?ShortUrl;

    /**
     * @return list<ShortUrl>
     */
    public function findByUser(User $user): array;

    public function createUserQueryBuilder(User $user): QueryBuilder;

    /**
     * @return list<ShortUrl>
     */
    public function findAll(): array;

    public function save(ShortUrl $shortUrl): void;

    public function remove(ShortUrl $shortUrl): void;
}
