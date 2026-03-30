<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShortUrl;
use App\Entity\User;
use App\Repository\ClickRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

final readonly class LinkService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ShortCodeGenerator $generator,
        private ClickRepository $clickRepository,
        private PaginatorInterface $paginator,
    ) {
    }

    /**
     * @return array{pagination: PaginationInterface<array-key, ShortUrl>, shortUrls: array<ShortUrl>, clickCounts: array<int, int>}
     */
    public function getPaginatedLinks(User $user, int $page, int $limit = 3): array
    {
        $query = $this->em->getRepository(ShortUrl::class)
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate($query, $page, $limit);

        /** @var array<ShortUrl> $shortUrls */
        $shortUrls = array_values((array) $pagination->getItems());
        $clickCounts = $this->clickRepository->countGroupedByShortUrl($shortUrls);

        return ['pagination' => $pagination, 'shortUrls' => $shortUrls, 'clickCounts' => $clickCounts];
    }

    /**
     * @return array{errors: list<string>, shortUrl?: ShortUrl}
     */
    public function createShortUrl(User $user, string $originalUrl): array
    {
        $errors = [];

        if (empty($originalUrl)) {
            $errors[] = 'Please enter a URL.';
        } elseif (!filter_var($originalUrl, \FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid URL.';
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl($originalUrl);
        $shortUrl->setCode($this->generator->generate());
        $shortUrl->setUser($user);

        try {
            $this->em->persist($shortUrl);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            $shortUrl->setCode($this->generator->generate());
            $this->em->flush();
        }

        return ['errors' => [], 'shortUrl' => $shortUrl];
    }

    public function deleteShortUrl(ShortUrl $shortUrl): void
    {
        $this->em->remove($shortUrl);
        $this->em->flush();
    }
}
