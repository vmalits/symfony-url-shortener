<?php

declare(strict_types=1);

namespace App\UI\Http\Web\Controller;

use App\Application\ShortUrl\Query\GetDashboardQuery;
use App\Application\ShortUrl\QueryHandler\GetDashboardHandler;
use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(GetDashboardHandler $handler, #[CurrentUser] User $user): Response
    {
        $data = $handler(
            new GetDashboardQuery($user->getId() ?? throw new \LogicException('User must have an ID'))
        );

        return $this->render('dashboard/index.html.twig', [
            'shortUrls' => $data['shortUrls'],
            'clickCounts' => $data['clickCounts'],
            'countries' => $data['countries'],
            'referrers' => $data['referrers'],
            'devices' => $data['devices'],
        ]);
    }
}
