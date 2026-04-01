<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testAcceptsValidEmail(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', $email->value());
    }

    public function testRejectsEmptyEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('');
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function testEquals(): void
    {
        $a = new Email('a@test.com');
        $b = new Email('a@test.com');
        $c = new Email('b@test.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', (string) $email);
    }
}