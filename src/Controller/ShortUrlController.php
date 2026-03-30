<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\TrackClickMessage;
use App\Repository\ShortUrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ShortUrlController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route('/{code}', name: 'app_redirect', methods: ['GET'], priority: -1)]
    public function redirectToUrl(
        string $code,
        ShortUrlRepository $repository,
        Request $request,
    ): Response {
        $originalUrl = $this->cache->get('short_url_'.$code, function (ItemInterface $item) use ($repository, $code) {
            $item->expiresAfter(3600);

            $shortUrl = $repository->findOneBy(['code' => $code]);

            if (!$shortUrl) {
                throw $this->createNotFoundException('Short URL not found');
            }

            return $shortUrl->getOriginalUrl();
        });

        $this->bus->dispatch(new TrackClickMessage(
            $code,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent'),
            $request->headers->get('referer'),
        ));

        return new RedirectResponse($originalUrl, Response::HTTP_FOUND);
    }
}
