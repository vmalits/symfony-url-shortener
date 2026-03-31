<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\QueryHandler;

use App\Application\ShortUrl\Query\GetUserLinksQuery;
use App\Domain\Click\Repository\ClickRepositoryInterface;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

final readonly class GetUserLinksHandler
{
    public function __construct(
        private ShortUrlRepositoryInterface $repository,
        private ClickRepositoryInterface $clickRepository,
        private UserRepositoryInterface $userRepository,
        private PaginatorInterface $paginator,
    ) {
    }

    /**
     * @return array{pagination: PaginationInterface, shortUrls: list<ShortUrl>, clickCounts: array<int, int>}
     */
    public function __invoke(GetUserLinksQuery $query): array
    {
        $user = $this->userRepository->findById($query->userId);

        if (null === $user) {
            return ['pagination' => $this->paginator->paginate([], 1, $query->limit), 'shortUrls' => [], 'clickCounts' => []];
        }

        $shortUrls = $this->repository->findByUser($user);

        $pagination = $this->paginator->paginate($shortUrls, $query->page, $query->limit);

        /** @var list<ShortUrl> $items */
        $items = array_values((array) $pagination->getItems());
        $clickCounts = $this->clickRepository->countGroupedByShortUrl($items);

        return ['pagination' => $pagination, 'shortUrls' => $items, 'clickCounts' => $clickCounts];
    }
}
