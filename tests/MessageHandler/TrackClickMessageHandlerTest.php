<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Click;
use App\Entity\ShortUrl;
use App\Message\TrackClickMessage;
use App\MessageHandler\TrackClickMessageHandler;
use App\Repository\ShortUrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TrackClickMessageHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ShortUrlRepository&MockObject $shortUrlRepository;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->shortUrlRepository = $this->createMock(ShortUrlRepository::class);
    }

    public function testHandlerCreatesClick(): void
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl('https://example.com');
        $shortUrl->setCode('testcode');

        $this->shortUrlRepository
            ->expects($this->once())
            ->method('find')
            ->with('testcode')
            ->willReturn($shortUrl);

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn (Click $click): bool => $click->getShortUrl() === $shortUrl
                && $click->getIp() === '127.0.0.1'
                && $click->getUserAgent() === 'TestAgent'));

        $this->em
            ->expects($this->once())
            ->method('flush');

        $handler = new TrackClickMessageHandler($this->shortUrlRepository, $this->em);

        $message = new TrackClickMessage('testcode', '127.0.0.1', 'TestAgent');
        $handler($message);
    }

    public function testHandlerDoesNothingWhenShortUrlNotFound(): void
    {
        $this->shortUrlRepository
            ->expects($this->once())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $this->em
            ->expects($this->never())
            ->method('persist');

        $this->em
            ->expects($this->never())
            ->method('flush');

        $handler = new TrackClickMessageHandler($this->shortUrlRepository, $this->em);

        $message = new TrackClickMessage('nonexistent', '127.0.0.1', 'TestAgent');
        $handler($message);
    }
}
