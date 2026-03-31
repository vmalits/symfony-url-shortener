<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    /**
     * @return list<User>
     */
    public function findAll(): array;

    public function save(User $user): void;

    public function upgradePassword(User $user, string $newHashedPassword): void;
}
