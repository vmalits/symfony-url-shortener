<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
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

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);
        foreach ($userRepository->findAll() as $user) {
            $this->em->remove($user);
        }
        $this->em->flush();

        /** @var ShortUrlRepositoryInterface $shortUrlRepository */
        $shortUrlRepository = $container->get(ShortUrlRepositoryInterface::class);
        foreach ($shortUrlRepository->findAll() as $shortUrl) {
            $this->em->remove($shortUrl);
        }
        $this->em->flush();

        /** @var CacheItemPoolInterface $cache */
        $cache = $container->get(CacheItemPoolInterface::class);
        $cache->clear();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $email = new Email('test@example.com');
        $user = User::create($email, $hasher->hashPassword(
            User::create($email, ''),
            'password',
        ));
        $this->em->persist($user);
        $this->em->flush();
        $this->user = $user;

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $container->get(UrlGeneratorInterface::class);
        $this->urlGenerator = $urlGenerator;
    }

    public function testCreateRequiresAuthentication(): void
    {
        $this->client->request('POST', $this->urlGenerator->generate('app_links_create'));

        self::assertResponseRedirects($this->urlGenerator->generate('app_login'));
    }

    public function testCreateShortUrl(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', $this->urlGenerator->generate('app_links'));

        $this->client->request('POST', $this->urlGenerator->generate('app_links_create'), [
            'originalUrl' => 'https://example.com',
            '_token' => $crawler->filter('input[name="_token"]')->first()->attr('value'),
        ]);

        self::assertResponseRedirects($this->urlGenerator->generate('app_links'));
    }

    public function testDeleteShortUrl(): void
    {
        $this->client->loginUser($this->user);

        $shortUrl = ShortUrl::create(
            new Url('https://example.com/delete'),
            new ShortCode('del01test'),
            $this->user,
        );
        $this->em->persist($shortUrl);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->urlGenerator->generate('app_links'));
        $token = $crawler->filter('input[name="_token"]')->last()->attr('value');

        $this->client->request('DELETE', $this->urlGenerator->generate('app_links_delete', ['id' => $shortUrl->getId()]), [
            '_token' => $token,
        ]);

        self::assertResponseRedirects($this->urlGenerator->generate('app_links'));
    }

    public function testRedirectDispatchesMessage(): void
    {
        $shortUrl = ShortUrl::create(
            new Url('https://example.com/redirect'),
            new ShortCode('rdct1test'),
            $this->user,
        );
        $this->em->persist($shortUrl);
        $this->em->flush();

        $this->client->request('GET', '/rdct1test');

        self::assertResponseRedirects('https://example.com/redirect');
    }
}
