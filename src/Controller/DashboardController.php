<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ClickRepository;
use App\Repository\ShortUrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ShortUrlRepository $shortUrlRepository,
        private readonly ClickRepository $clickRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $shortUrls = $this->shortUrlRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);
        $clickCounts = $this->clickRepository->countGroupedByShortUrl($shortUrls);

        return $this->render('dashboard/index.html.twig', [
            'shortUrls' => $shortUrls,
            'clickCounts' => $clickCounts,
        ]);
    }
}
