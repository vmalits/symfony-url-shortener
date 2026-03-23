<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\ShortUrl;
use App\Entity\User;
use App\Repository\ClickRepository;
use App\Repository\ShortUrlRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ShortUrlControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);
        foreach ($userRepository->findAll() as $user) {
            $this->em->remove($user);
        }
        $this->em->flush();

        /** @var ShortUrlRepository $shortUrlRepository */
        $shortUrlRepository = $container->get(ShortUrlRepository::class);
        foreach ($shortUrlRepository->findAll() as $shortUrl) {
            $this->em->remove($shortUrl);
        }
        $this->em->flush();

        /** @var ClickRepository $clickRepository */
        $clickRepository = $container->get(ClickRepository::class);
        foreach ($clickRepository->findAll() as $click) {
            $this->em->remove($click);
        }
        $this->em->flush();

        /** @var CacheItemPoolInterface $cache */
        $cache = $container->get(CacheItemPoolInterface::class);
        $cache->clear();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $this->user = new User();
        $this->user->setEmail('shorturl@test.com');
        $this->user->setPassword($passwordHasher->hashPassword($this->user, 'password'));
        $this->em->persist($this->user);
        $this->em->flush();

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $container->get(UrlGeneratorInterface::class);
        $this->urlGenerator = $urlGenerator;
    }

    public function testCreateRequiresAuthentication(): void
    {
        $this->client->request('POST', $this->urlGenerator->generate('app_links_create'), [
            'originalUrl' => 'https://example.com',
            '_token' => 'test',
        ]);

        self::assertResponseRedirects($this->urlGenerator->generate('app_login'));
    }

    public function testCreateShortUrl(): void
    {
        $this->client->loginUser($this->user);

        $crawler = $this->client->request('GET', $this->urlGenerator->generate('app_dashboard'));
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', $this->urlGenerator->generate('app_links_create'), [
            'originalUrl' => 'https://example.com',
            '_token' => $token,
        ]);

        self::assertResponseRedirects($this->urlGenerator->generate('app_dashboard'));
    }

    public function testDeleteShortUrl(): void
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl('https://example.com');
        $shortUrl->setCode('testcode');
        $shortUrl->setUser($this->user);
        $this->em->persist($shortUrl);
        $this->em->flush();

        $this->client->loginUser($this->user);

        $crawler = $this->client->request('GET', $this->urlGenerator->generate('app_dashboard'));
        $token = $crawler->filter('form[action*="'.$shortUrl->getId().'"] input[name="_token"]')
            ->attr('value');

        $this->client->request('DELETE', $this->urlGenerator
            ->generate('app_links_delete', ['id' => $shortUrl->getId()]), [
                '_token' => $token,
            ]);

        self::assertResponseRedirects($this->urlGenerator->generate('app_dashboard'));
    }

    public function testRedirectDispatchesMessage(): void
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl('https://example.com/redirect');
        $shortUrl->setCode('rdct1');
        $shortUrl->setUser($this->user);
        $this->em->persist($shortUrl);
        $this->em->flush();

        $this->client->request('GET', '/rdct1');

        self::assertResponseRedirects('https://example.com/redirect');
    }
}
