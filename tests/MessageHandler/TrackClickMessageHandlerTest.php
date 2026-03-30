<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Click;
use App\Entity\ShortUrl;
use App\Message\TrackClickMessage;
use App\MessageHandler\TrackClickMessageHandler;
use App\Repository\ShortUrlRepository;
use App\Service\GeoIpInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TrackClickMessageHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ShortUrlRepository&MockObject $shortUrlRepository;
    private GeoIpInterface&MockObject $geoIpService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->shortUrlRepository = $this->createMock(ShortUrlRepository::class);
        $this->geoIpService = $this->createMock(GeoIpInterface::class);
    }

    public function testHandlerCreatesClick(): void
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl('https://example.com');
        $shortUrl->setCode('testcode');

        $this->shortUrlRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'testcode'])
            ->willReturn($shortUrl);

        $this->geoIpService
            ->expects($this->once())
            ->method('getCountryCode')
            ->with('127.0.0.1')
            ->willReturn('US');

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (Click $click): bool => $click->getShortUrl() === $shortUrl
                && '127.0.0.1' === $click->getIp()
                && 'TestAgent' === $click->getUserAgent()
                && 'https://google.com' === $click->getReferrer()
                && 'US' === $click->getCountry()));

        $this->em
            ->expects($this->once())
            ->method('flush');

        $handler = new TrackClickMessageHandler($this->shortUrlRepository, $this->em, $this->geoIpService);

        $message = new TrackClickMessage('testcode', '127.0.0.1', 'TestAgent', 'https://google.com');
        $handler($message);
    }

    public function testHandlerDoesNothingWhenShortUrlNotFound(): void
    {
        $this->shortUrlRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'nonexistent'])
            ->willReturn(null);

        $this->em
            ->expects($this->never())
            ->method('persist');

        $this->em
            ->expects($this->never())
            ->method('flush');

        $handler = new TrackClickMessageHandler($this->shortUrlRepository, $this->em, $this->geoIpService);

        $message = new TrackClickMessage('nonexistent', '127.0.0.1', 'TestAgent', null);
        $handler($message);
    }
}
