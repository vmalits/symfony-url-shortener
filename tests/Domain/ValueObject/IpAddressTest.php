<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\Click\ValueObject\IpAddress;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    public function testAcceptsValidIpv4(): void
    {
        $ip = new IpAddress('192.168.1.1');
        $this->assertSame('192.168.1.1', $ip->value());
    }

    public function testAcceptsValidIpv6(): void
    {
        $ip = new IpAddress('::1');
        $this->assertSame('::1', $ip->value());
    }

    public function testAcceptsEmptyString(): void
    {
        $ip = new IpAddress('');
        $this->assertSame('', $ip->value());
    }

    public function testRejectsInvalidIp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IpAddress('not-an-ip');
    }

    public function testToString(): void
    {
        $ip = new IpAddress('127.0.0.1');
        $this->assertSame('127.0.0.1', (string) $ip);
    }
}