<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\ShortUrl;
use App\Entity\User;
use App\Message\TrackClickMessage;
use App\MessageHandler\TrackClickMessageHandler;
use App\Repository\ClickRepository;
use App\Repository\ShortUrlRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TrackClickMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ShortUrl $shortUrl;

    protected function setUp(): void
    {
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        /** @var ClickRepository $clickRepository */
        $clickRepository = $container->get(ClickRepository::class);
        foreach ($clickRepository->findAll() as $click) {
            $this->em->remove($click);
        }
        $this->em->flush();

        /** @var ShortUrlRepository $shortUrlRepository */
        $shortUrlRepository = $container->get(ShortUrlRepository::class);
        foreach ($shortUrlRepository->findAll() as $shortUrl) {
            $this->em->remove($shortUrl);
        }
        $this->em->flush();

        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);
        foreach ($userRepository->findAll() as $user) {
            $this->em->remove($user);
        }
        $this->em->flush();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('handler@test.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $this->em->persist($user);

        $this->shortUrl = new ShortUrl();
        $this->shortUrl->setOriginalUrl('https://example.com');
        $this->shortUrl->setCode('testcode');
        $this->shortUrl->setUser($user);
        $this->em->persist($this->shortUrl);
        $this->em->flush();

        self::assertNotNull($this->shortUrl->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->clear();
    }

    public function testHandlerCreatesClick(): void
    {
        /** @var ShortUrlRepository $shortUrlRepository */
        $shortUrlRepository = static::getContainer()->get(ShortUrlRepository::class);

        $handler = new TrackClickMessageHandler($shortUrlRepository, $this->em);

        $message = new TrackClickMessage(
            (int) $this->shortUrl->getId(),
            '127.0.0.1',
            'Mozilla/5.0',
        );

        $handler($message);

        /** @var ClickRepository $clickRepository */
        $clickRepository = static::getContainer()->get(ClickRepository::class);
        $clicks = $clickRepository->findBy(['shortUrl' => $this->shortUrl]);

        self::assertCount(1, $clicks);
        self::assertSame('127.0.0.1', $clicks[0]->getIp());
        self::assertSame('Mozilla/5.0', $clicks[0]->getUserAgent());
    }

    public function testHandlerIgnoresNonExistentShortUrl(): void
    {
        /** @var ShortUrlRepository $shortUrlRepository */
        $shortUrlRepository = static::getContainer()->get(ShortUrlRepository::class);

        $handler = new TrackClickMessageHandler($shortUrlRepository, $this->em);

        $message = new TrackClickMessage(
            99999,
            '127.0.0.1',
            'Mozilla/5.0',
        );

        $handler($message);

        /** @var ClickRepository $clickRepository */
        $clickRepository = static::getContainer()->get(ClickRepository::class);
        $clicks = $clickRepository->findAll();

        self::assertCount(0, $clicks);
    }
}
