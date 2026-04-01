<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ShortUrl\Exception\InvalidShortCodeException;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use PHPUnit\Framework\TestCase;

final class ShortCodeTest extends TestCase
{
    public function testAcceptsValidCode(): void
    {
        $code = new ShortCode('abc123');
        $this->assertSame('abc123', $code->value());
    }

    public function testAcceptsMaxLengthCode(): void
    {
        $code = new ShortCode('abcdefghij');
        $this->assertSame('abcdefghij', $code->value());
    }

    public function testRejectsEmptyCode(): void
    {
        $this->expectException(InvalidShortCodeException::class);
        new ShortCode('');
    }

    public function testRejectsTooLongCode(): void
    {
        $this->expectException(InvalidShortCodeException::class);
        new ShortCode('abcdefghijk');
    }

    public function testEquals(): void
    {
        $a = new ShortCode('abc');
        $b = new ShortCode('abc');
        $c = new ShortCode('xyz');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $code = new ShortCode('abc123');
        $this->assertSame('abc123', (string) $code);
    }
}