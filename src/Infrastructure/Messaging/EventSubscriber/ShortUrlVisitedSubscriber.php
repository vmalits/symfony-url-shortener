<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\EventSubscriber;

use App\Domain\Click\Entity\Click;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\Click\Service\GeoIpInterface;
use App\Domain\ShortUrl\Event\ShortUrlVisitedEvent;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ShortUrlVisitedSubscriber
{
    public function __construct(
        private ShortUrlRepositoryInterface $shortUrlRepository,
        private ClickRepositoryInterface $clickRepository,
        private GeoIpInterface $geoIpService,
    ) {
    }

    #[AsEventListener]
    public function onShortUrlVisited(ShortUrlVisitedEvent $event): void
    {
        $shortUrl = $this->shortUrlRepository->findByCode($event->code);

        if (null === $shortUrl) {
            return;
        }

        $click = new Click(
            $shortUrl,
            $event->ip,
            $event->userAgent,
            $this->geoIpService->getCountryCode($event->ip),
            $event->referrer,
        );

        $this->clickRepository->save($click);
    }
}
