<?php

declare(strict_types=1);

namespace App\UI\Http\Web\Controller;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\UI\Http\Web\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailString = $form->get('email')->getData();
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $email = new Email($emailString);
            $hashedPassword = $passwordHasher->hashPassword(
                User::create($email, ''),
                $plainPassword,
            );

            $user = User::create($email, $hashedPassword);

            try {
                $this->userRepository->save($user);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'This email is already registered.');

                return $this->redirectToRoute('app_register');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
