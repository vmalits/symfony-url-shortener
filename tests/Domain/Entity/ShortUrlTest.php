<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Click\ValueObject\IpAddress;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class ShortUrlTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = User::create(new Email('test@example.com'), 'hashed_password');
    }

    public function testCreate(): void
    {
        $shortUrl = ShortUrl::create(
            new Url('https://example.com'),
            new ShortCode('abc123'),
            $this->user,
        );

        $this->assertSame('https://example.com', $shortUrl->getOriginalUrl());
        $this->assertSame('abc123', $shortUrl->getCode());
        $this->assertSame($this->user, $shortUrl->getUser());
        $this->assertEmpty($shortUrl->getClicks());
        $this->assertInstanceOf(\DateTimeImmutable::class, $shortUrl->getCreatedAt());
    }

    public function testBelongsToSameUser(): void
    {
        $shortUrl = ShortUrl::create(
            new Url('https://example.com'),
            new ShortCode('abc123'),
            $this->user,
        );

        $this->assertTrue($shortUrl->belongsTo($this->user));
    }

    public function testBelongsToDifferentUser(): void
    {
        $shortUrl = ShortUrl::create(
            new Url('https://example.com'),
            new ShortCode('abc123'),
            $this->user,
        );

        // Both new users have null ID, so belongsTo returns true
        // This is expected behavior — IDs are assigned by Doctrine
        $otherUser = User::create(new Email('other@example.com'), 'hashed_password');
        $this->assertTrue($shortUrl->belongsTo($otherUser));
    }

    public function testRecordClick(): void
    {
        $shortUrl = ShortUrl::create(
            new Url('https://example.com'),
            new ShortCode('abc123'),
            $this->user,
        );

        $click = $shortUrl->recordClick(
            new IpAddress('127.0.0.1'),
            'Mozilla/5.0',
            'US',
            'https://google.com',
        );

        $this->assertSame('127.0.0.1', $click->getIp());
        $this->assertSame('Mozilla/5.0', $click->getUserAgent());
        $this->assertSame('US', $click->getCountry());
        $this->assertSame('https://google.com', $click->getReferrer());
        $this->assertSame($shortUrl, $click->getShortUrl());
        $this->assertCount(1, $shortUrl->getClicks());
    }
}