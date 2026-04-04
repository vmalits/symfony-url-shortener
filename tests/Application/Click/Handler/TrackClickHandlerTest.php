<?php

declare(strict_types=1);

namespace App\Tests\Application\Click\Handler;

use App\Application\Click\Command\TrackClickCommand;
use App\Application\Click\Handler\TrackClickHandler;
use App\Domain\Click\Entity\Click;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\Click\Service\GeoIpInterface;
use App\Domain\Click\ValueObject\IpAddress;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TrackClickHandlerTest extends TestCase
{
    private ShortUrlRepositoryInterface&MockObject $shortUrlRepository;
    private ClickRepositoryInterface&MockObject $clickRepository;
    private GeoIpInterface $geoIpService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->shortUrlRepository = $this->createMock(ShortUrlRepositoryInterface::class);
        $this->clickRepository = $this->createMock(ClickRepositoryInterface::class);
        $this->geoIpService = $this->createStub(GeoIpInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
    }

    public function testHandlerCreatesClick(): void
    {
        $shortUrl = $this->createStub(ShortUrl::class);

        $geoIpService = $this->createMock(GeoIpInterface::class);
        $geoIpService
            ->expects($this->once())
            ->method('getCountryCode')
            ->with('127.0.0.1')
            ->willReturn('US');

        $this->shortUrlRepository
            ->expects($this->once())
            ->method('findByCode')
            ->with('testcode')
            ->willReturn($shortUrl);

        $this->clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Click $click): bool => $click->getShortUrl() === $shortUrl
                && '127.0.0.1' === $click->getIp()
                && 'TestAgent' === $click->getUserAgent()
                && 'https://google.com' === $click->getReferrer()
                && 'US' === $click->getCountry()));

        $handler = new TrackClickHandler($this->shortUrlRepository, $this->clickRepository, $geoIpService, $this->logger);

        $command = new TrackClickCommand('testcode', new IpAddress('127.0.0.1'), 'TestAgent', 'https://google.com');
        $handler($command);
    }

    public function testHandlerDoesNothingWhenShortUrlNotFound(): void
    {
        $this->shortUrlRepository
            ->expects($this->once())
            ->method('findByCode')
            ->with('nonexistent')
            ->willReturn(null);

        $this->clickRepository
            ->expects($this->never())
            ->method('save');

        $handler = new TrackClickHandler($this->shortUrlRepository, $this->clickRepository, $this->geoIpService, $this->logger);

        $command = new TrackClickCommand('nonexistent', new IpAddress('127.0.0.1'), 'TestAgent', null);
        $handler($command);
    }
}
