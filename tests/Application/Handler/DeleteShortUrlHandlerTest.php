<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\ShortUrl\Command\DeleteShortUrlCommand;
use App\Application\ShortUrl\Handler\DeleteShortUrlHandler;
use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Exception\ShortUrlNotFoundException;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class DeleteShortUrlHandlerTest extends TestCase
{
    private ShortUrlRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShortUrlRepositoryInterface::class);
    }

    public function testDeletesOwnShortUrl(): void
    {
        $user = User::create(new Email('owner@test.com'), 'hashed');
        $this->setUserId($user, 1);
        $shortUrl = ShortUrl::create(new Url('https://example.com'), new ShortCode('abc123'), $user);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($shortUrl);

        $this->repository
            ->expects($this->once())
            ->method('remove')
            ->with($shortUrl);

        $handler = new DeleteShortUrlHandler($this->repository);
        $handler(new DeleteShortUrlCommand(1, 1));
    }

    public function testThrowsWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $handler = new DeleteShortUrlHandler($this->repository);

        $this->expectException(ShortUrlNotFoundException::class);
        $handler(new DeleteShortUrlCommand(999, 1));
    }

    public function testThrowsWhenNotOwner(): void
    {
        $owner = User::create(new Email('owner@test.com'), 'hashed');
        $this->setUserId($owner, 1);
        $shortUrl = ShortUrl::create(new Url('https://example.com'), new ShortCode('abc123'), $owner);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($shortUrl);

        $handler = new DeleteShortUrlHandler($this->repository);

        $this->expectException(AccessDeniedException::class);
        $handler(new DeleteShortUrlCommand(1, 999));
    }

    private function setUserId(User $user, int $id): void
    {
        $ref = new \ReflectionProperty($user, 'id');
        $ref->setValue($user, $id);
    }
}
