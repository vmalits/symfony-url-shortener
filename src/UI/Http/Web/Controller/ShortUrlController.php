<?php

declare(strict_types=1);

namespace App\UI\Http\Web\Controller;

use App\Application\Click\Command\TrackClickCommand;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Infrastructure\Cache\RedirectCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ShortUrlController extends AbstractController
{
    public function __construct(
        private readonly RedirectCache $redirectCache,
        private readonly MessageBusInterface $bus,
        private readonly ShortUrlRepositoryInterface $repository,
    ) {
    }

    #[Route('/{code}', name: 'app_redirect', methods: ['GET'], priority: -1)]
    public function redirectToUrl(string $code, Request $request): Response
    {
        $originalUrl = $this->redirectCache->getOriginalUrl($code, $this->repository);

        if (null === $originalUrl) {
            throw $this->createNotFoundException('Short URL not found');
        }

        $this->bus->dispatch(new TrackClickCommand(
            $code,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent'),
            $request->headers->get('referer'),
        ));

        return new RedirectResponse($originalUrl, Response::HTTP_FOUND);
    }
}
