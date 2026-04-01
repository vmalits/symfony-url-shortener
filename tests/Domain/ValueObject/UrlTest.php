<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ShortUrl\Exception\InvalidUrlException;
use App\Domain\ShortUrl\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function testAcceptsValidHttpUrl(): void
    {
        $url = new Url('http://example.com');
        $this->assertSame('http://example.com', $url->value());
    }

    public function testAcceptsValidHttpsUrl(): void
    {
        $url = new Url('https://example.com/path?q=1');
        $this->assertSame('https://example.com/path?q=1', $url->value());
    }

    public function testRejectsEmptyUrl(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('');
    }

    public function testRejectsInvalidUrl(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('not-a-url');
    }

    public function testRejectsJavascriptScheme(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('javascript:alert(1)');
    }

    public function testRejectsFileScheme(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('file:///etc/passwd');
    }

    public function testRejectsDataScheme(): void
    {
        $this->expectException(InvalidUrlException::class);
        new Url('data:text/html,<h1>test</h1>');
    }

    public function testEquals(): void
    {
        $a = new Url('https://example.com');
        $b = new Url('https://example.com');
        $c = new Url('https://other.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $url = new Url('https://example.com');
        $this->assertSame('https://example.com', (string) $url);
    }
}
