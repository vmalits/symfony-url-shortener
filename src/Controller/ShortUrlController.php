<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ShortUrl;
use App\Entity\User;
use App\Message\TrackClickMessage;
use App\Repository\ShortUrlRepository;
use App\Security\ShortUrlVoter;
use App\Service\ShortCodeGenerator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShortUrlController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShortCodeGenerator $generator,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/links', name: 'app_links_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('create_link', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_dashboard');
        }

        $originalUrl = $request->request->getString('originalUrl');

        $errors = $this->validator->validate($originalUrl, new Url());
        if (\count($errors) > 0) {
            $this->addFlash('error', 'Please enter a valid URL.');

            return $this->redirectToRoute('app_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl($originalUrl);
        $shortUrl->setCode($this->generator->generate());
        $shortUrl->setUser($user);

        try {
            $this->em->persist($shortUrl);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            $shortUrl->setCode($this->generator->generate());
            $this->em->flush();
        }

        $this->addFlash('success', \sprintf('Link created: %s',
            $this->generateUrl('app_redirect', ['code' => $shortUrl->getCode()],
                UrlGeneratorInterface::ABSOLUTE_URL)));

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/links/{id}', name: 'app_links_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(ShortUrl $shortUrl, Request $request): Response
    {
        if (
            !$this->isCsrfTokenValid('delete_link_'.$shortUrl->getId(),
                $request->request->getString('_token'))
        ) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_dashboard');
        }

        $this->denyAccessUnlessGranted(ShortUrlVoter::DELETE, $shortUrl);

        $this->em->remove($shortUrl);
        $this->em->flush();

        $this->addFlash('success', 'Link deleted.');

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{code}', name: 'app_redirect', methods: ['GET'], priority: -1)]
    public function redirectToUrl(
        string $code,
        ShortUrlRepository $repository,
        Request $request,
        MessageBusInterface $bus,
    ): Response {
        $shortUrl = $repository->findOneBy(['code' => $code]);

        if (!$shortUrl || !$shortUrl->getId()) {
            throw $this->createNotFoundException('Short URL not found');
        }

        $bus->dispatch(new TrackClickMessage(
            $shortUrl->getId(),
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent'),
        ));

        return new RedirectResponse($shortUrl->getOriginalUrl(), Response::HTTP_FOUND);
    }
}
