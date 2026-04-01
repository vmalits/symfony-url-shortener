<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\ShortUrl\Command\CreateShortUrlCommand;
use App\Application\ShortUrl\Handler\CreateShortUrlHandler;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\ShortUrl\Service\ShortCodeGeneratorInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateShortUrlHandlerTest extends TestCase
{
    private ShortUrlRepositoryInterface&MockObject $repository;
    private ShortCodeGeneratorInterface&MockObject $generator;
    private UserRepositoryInterface&MockObject $userRepository;

    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShortUrlRepositoryInterface::class);
        $this->generator = $this->createMock(ShortCodeGeneratorInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->user = User::create(new Email('test@example.com'), 'hashed');
    }

    public function testCreatesShortUrl(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($this->user);

        $this->generator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('abc123');

        $this->repository
            ->expects($this->once())
            ->method('save');

        $handler = new CreateShortUrlHandler($this->repository, $this->generator, $this->userRepository);
        $result = $handler(new CreateShortUrlCommand('https://example.com', 1));

        $this->assertSame('https://example.com', $result->getOriginalUrl());
        $this->assertSame('abc123', $result->getCode());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $handler = new CreateShortUrlHandler($this->repository, $this->generator, $this->userRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        $handler(new CreateShortUrlCommand('https://example.com', 999));
    }

    public function testRetriesOnDuplicateCode(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->user);

        $this->generator
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('dup1', 'dup2', 'uniq1');

        $this->repository
            ->expects($this->exactly(3))
            ->method('save')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($this->createMock(UniqueConstraintViolationException::class)),
                $this->throwException($this->createMock(UniqueConstraintViolationException::class)),
                null,
            );

        $handler = new CreateShortUrlHandler($this->repository, $this->generator, $this->userRepository);
        $result = $handler(new CreateShortUrlCommand('https://example.com', 1));

        $this->assertSame('uniq1', $result->getCode());
    }

    public function testThrowsAfterMaxRetries(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->user);

        $this->generator
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturn('dup1');

        $this->repository
            ->expects($this->exactly(3))
            ->method('save')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));

        $handler = new CreateShortUrlHandler($this->repository, $this->generator, $this->userRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate unique short code');

        $handler(new CreateShortUrlCommand('https://example.com', 1));
    }
}
