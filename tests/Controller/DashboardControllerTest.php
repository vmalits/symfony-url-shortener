<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        \assert($passwordHasher instanceof UserPasswordHasherInterface);

        $user = new User();
        $user->setEmail('dashboard@test.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/dashboard');

        self::assertResponseIsSuccessful();
    }
}
