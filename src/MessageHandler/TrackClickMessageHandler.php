<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Click;
use App\Message\TrackClickMessage;
use App\Repository\ShortUrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TrackClickMessageHandler
{
    public function __construct(
        private ShortUrlRepository $shortUrlRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(TrackClickMessage $message): void
    {
        $shortUrl = $this->shortUrlRepository->findOneBy(['code' => $message->code]);

        if (!$shortUrl) {
            return;
        }

        $click = new Click();
        $click->setShortUrl($shortUrl);
        $click->setIp($message->ip);
        $click->setUserAgent($message->userAgent);
        $click->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($click);
        $this->em->flush();
    }
}
