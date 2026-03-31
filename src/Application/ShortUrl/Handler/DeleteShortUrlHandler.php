<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\Handler;

use App\Application\ShortUrl\Command\DeleteShortUrlCommand;
use App\Domain\ShortUrl\Exception\ShortUrlNotFoundException;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;

final readonly class DeleteShortUrlHandler
{
    public function __construct(
        private ShortUrlRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeleteShortUrlCommand $command): void
    {
        $shortUrl = $this->repository->findById($command->shortUrlId);

        if (null === $shortUrl) {
            throw ShortUrlNotFoundException::byId($command->shortUrlId);
        }

        $this->repository->remove($shortUrl);
    }
}
