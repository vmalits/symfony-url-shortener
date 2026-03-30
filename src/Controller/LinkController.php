<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ShortUrl;
use App\Entity\User;
use App\Security\ShortUrlVoter;
use App\Service\LinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;

final class LinkController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/links', name: 'app_links', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, LinkService $linkService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = $linkService->getPaginatedLinks(
            $user,
            $request->query->getInt('page', 1),
        );

        return $this->render('links/index.html.twig', [
            'pagination' => $data['pagination'],
            'shortUrls' => $data['shortUrls'],
            'clickCounts' => $data['clickCounts'],
        ]);
    }

    #[Route('/links/create', name: 'app_links_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, LinkService $linkService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $result = $linkService->createShortUrl(
            $user,
            (string) $request->request->get('originalUrl', ''),
        );

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->addFlash('error', $error);
            }
        } elseif (isset($result['shortUrl'])) {
            $link = $this->generateUrl('app_redirect', ['code' => $result['shortUrl']->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->addFlash('success', 'Link created successfully!');
            $this->addFlash('success', 'Link: '.$link);
        }

        return $this->redirectToRoute('app_links');
    }

    #[Route('/links/{id}/delete', name: 'app_links_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(ShortUrl $shortUrl, Request $request, LinkService $linkService): Response
    {
        if (!$this->isCsrfTokenValid('delete_link_'.$shortUrl->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_links');
        }

        $this->denyAccessUnlessGranted(ShortUrlVoter::DELETE, $shortUrl);

        $this->cache->delete('short_url_'.$shortUrl->getCode());
        $linkService->deleteShortUrl($shortUrl);

        $this->addFlash('success', 'Link deleted.');

        return $this->redirectToRoute('app_links');
    }
}
