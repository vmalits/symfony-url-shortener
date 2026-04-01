<?php

declare(strict_types=1);

namespace App\UI\Http\Web\Controller;

use App\Application\ShortUrl\Command\CreateShortUrlCommand;
use App\Application\ShortUrl\Command\DeleteShortUrlCommand;
use App\Application\ShortUrl\Handler\CreateShortUrlHandler;
use App\Application\ShortUrl\Handler\DeleteShortUrlHandler;
use App\Application\ShortUrl\Query\GetUserLinksQuery;
use App\Application\ShortUrl\QueryHandler\GetUserLinksHandler;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Exception\InvalidUrlException;
use App\Domain\User\Entity\User;
use App\Infrastructure\Cache\RedirectCache;
use App\Infrastructure\Security\ShortUrlVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LinkController extends AbstractController
{
    public function __construct(
        private readonly RedirectCache $redirectCache,
        #[Autowire(service: 'limiter.link_creation')] private readonly RateLimiterFactory $linkCreationLimiter,
    ) {
    }

    #[Route('/links', name: 'app_links', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, GetUserLinksHandler $handler, #[CurrentUser] User $user): Response
    {
        $data = $handler(new GetUserLinksQuery(
            $user->getId() ?? throw new \LogicException('User must have an ID'),
            $request->query->getInt('page', 1),
        ));

        return $this->render('links/index.html.twig', [
            'pagination' => $data['pagination'],
            'shortUrls' => $data['shortUrls'],
            'clickCounts' => $data['clickCounts'],
        ]);
    }

    #[Route('/links/create', name: 'app_links_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, CreateShortUrlHandler $handler, #[CurrentUser] User $user): Response
    {
        $limiter = $this->linkCreationLimiter->create($user->getUserIdentifier());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $this->addFlash('error', 'Too many requests. Please wait before creating another link.');

            return $this->redirectToRoute('app_links');
        }

        try {
            $handler(new CreateShortUrlCommand(
                $request->request->getString('originalUrl'),
                $user->getId() ?? throw new \LogicException('User must have an ID'),
            ));

            $this->addFlash('success', 'Link created successfully!');
        } catch (InvalidUrlException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_links');
    }

    #[Route('/links/{id}/delete', name: 'app_links_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        ShortUrl $shortUrl,
        Request $request,
        DeleteShortUrlHandler $handler,
        #[CurrentUser] User $user,
    ): Response {
        if (
            !$this->isCsrfTokenValid('delete_link_'.$shortUrl->getId(),
                $request->request->getString('_token')
            )
        ) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_links');
        }

        $this->denyAccessUnlessGranted(ShortUrlVoter::DELETE, $shortUrl);

        $code = $shortUrl->getCode();
        $handler(new DeleteShortUrlCommand(
            $shortUrl->getId() ?? throw new \LogicException('ShortUrl must have an ID'),
            $user->getId() ?? throw new \LogicException('User must have an ID'),
        ));

        $this->redirectCache->invalidate($code);

        $this->addFlash('success', 'Link deleted.');

        return $this->redirectToRoute('app_links');
    }
}
