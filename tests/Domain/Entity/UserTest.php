<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreate(): void
    {
        $user = User::create(new Email('test@example.com'), 'hashed_password');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertNull($user->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $user = User::create(new Email('test@example.com'), 'hashed_password');

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetUserIdentifier(): void
    {
        $user = User::create(new Email('test@example.com'), 'hashed_password');

        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testChangePassword(): void
    {
        $user = User::create(new Email('test@example.com'), 'old_hash');

        $user->changePassword('new_hash');

        $this->assertSame('new_hash', $user->getPassword());
    }
}