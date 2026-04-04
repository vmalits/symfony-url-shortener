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
    private UserRepositoryInterface&MockObject $userRepository;

    private User $user;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->user = User::create(new Email('test@example.com'), 'hashed');
    }

    public function testCreatesShortUrl(): void
    {
        $repository = $this->createMock(ShortUrlRepositoryInterface::class);
        $generator = $this->createMock(ShortCodeGeneratorInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($this->user);

        $generator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('abc123');

        $repository
            ->expects($this->once())
            ->method('save');

        $handler = new CreateShortUrlHandler($repository, $generator, $this->userRepository);
        $result = $handler(new CreateShortUrlCommand('https://example.com', 1));

        $this->assertSame('https://example.com', $result->getOriginalUrl());
        $this->assertSame('abc123', $result->getCode());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $repository = $this->createStub(ShortUrlRepositoryInterface::class);
        $generator = $this->createStub(ShortCodeGeneratorInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $handler = new CreateShortUrlHandler($repository, $generator, $this->userRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with id "999" not found.');

        $handler(new CreateShortUrlCommand('https://example.com', 999));
    }

    public function testRetriesOnDuplicateCode(): void
    {
        $repository = $this->createMock(ShortUrlRepositoryInterface::class);
        $generator = $this->createMock(ShortCodeGeneratorInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->user);

        $generator
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('dup1', 'dup2', 'uniq1');

        $repository
            ->expects($this->exactly(3))
            ->method('save')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($this->createStub(UniqueConstraintViolationException::class)),
                $this->throwException($this->createStub(UniqueConstraintViolationException::class)),
                null,
            );

        $handler = new CreateShortUrlHandler($repository, $generator, $this->userRepository);
        $result = $handler(new CreateShortUrlCommand('https://example.com', 1));

        $this->assertSame('uniq1', $result->getCode());
    }

    public function testThrowsAfterMaxRetries(): void
    {
        $repository = $this->createMock(ShortUrlRepositoryInterface::class);
        $generator = $this->createMock(ShortCodeGeneratorInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->user);

        $generator
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturn('dup1');

        $repository
            ->expects($this->exactly(3))
            ->method('save')
            ->willThrowException($this->createStub(UniqueConstraintViolationException::class));

        $handler = new CreateShortUrlHandler($repository, $generator, $this->userRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate a unique short code after 3 attempts.');

        $handler(new CreateShortUrlCommand('https://example.com', 1));
    }
}
