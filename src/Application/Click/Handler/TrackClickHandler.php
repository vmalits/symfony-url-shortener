<?php

declare(strict_types=1);

namespace App\Application\Click\Handler;

use App\Application\Click\Command\TrackClickCommand;
use App\Domain\Click\Entity\Click;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\Click\Service\GeoIpInterface;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TrackClickHandler
{
    public function __construct(
        private ShortUrlRepositoryInterface $shortUrlRepository,
        private ClickRepositoryInterface $clickRepository,
        private GeoIpInterface $geoIpService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TrackClickCommand $message): void
    {
        $shortUrl = $this->shortUrlRepository->findByCode($message->code);

        if (null === $shortUrl) {
            $this->logger->warning('Click tracked for non-existent short code', ['code' => $message->code]);

            return;
        }

        $click = new Click(
            $shortUrl,
            $message->ip,
            $message->userAgent,
            $this->geoIpService->getCountryCode($message->ip->value()),
            $message->referrer,
        );

        $this->clickRepository->save($click);
    }
}
