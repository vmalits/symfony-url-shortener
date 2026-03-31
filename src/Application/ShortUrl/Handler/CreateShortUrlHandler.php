<?php

declare(strict_types=1);

namespace App\Application\ShortUrl\Handler;

use App\Application\ShortUrl\Command\CreateShortUrlCommand;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\ShortUrl\Service\ShortCodeGeneratorInterface;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class CreateShortUrlHandler
{
    public function __construct(
        private ShortUrlRepositoryInterface $repository,
        private ShortCodeGeneratorInterface $generator,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateShortUrlCommand $command): ShortUrl
    {
        $user = $this->userRepository->findById($command->userId);

        if (null === $user) {
            throw new \RuntimeException('User not found.');
        }

        $originalUrl = new Url($command->originalUrl);

        for ($i = 0; $i < 3; ++$i) {
            $shortUrl = ShortUrl::create(
                $originalUrl,
                new ShortCode($this->generator->generate()),
                $user,
            );

            try {
                $this->repository->save($shortUrl);

                return $shortUrl;
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new \RuntimeException('Failed to generate unique short code');
    }
}
