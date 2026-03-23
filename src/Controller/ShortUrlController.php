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
use Symfony\Component\Validator\Constraints\Url as UrlConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ShortUrlController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShortCodeGenerator $generator,
        private readonly ValidatorInterface $validator,
        private readonly CacheInterface $cache,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $shortUrls = $this->em->getRepository(ShortUrl::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('dashboard/index.html.twig', [
            'shortUrls' => $shortUrls,
        ]);
    }

    #[Route('/links/create', name: 'app_links_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $originalUrl = (string) $request->request->get('originalUrl', '');
        $errors = [];

        if (empty($originalUrl)) {
            $errors[] = 'Please enter a URL.';
        } else {
            $violations = $this->validator->validate($originalUrl, [new UrlConstraint()]);
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        }

        if (empty($errors)) {
            $shortUrl = new ShortUrl();
            $shortUrl->setOriginalUrl($originalUrl);
            $shortUrl->setCode($this->generator->generate());
            $shortUrl->setUser($user);
            $shortUrl->setCreatedAt(new \DateTimeImmutable());

            try {
                $this->em->persist($shortUrl);
                $this->em->flush();
            } catch (UniqueConstraintViolationException) {
                $shortUrl->setCode($this->generator->generate());
                $this->em->flush();
            }

            $this->addFlash('success', 'Link created successfully!');
            $this->addFlash(
                'success', 'Link: '.$this->generateUrl(
                    'app_redirect', ['code' => $shortUrl->getCode()],
                    UrlGeneratorInterface::ABSOLUTE_URL));
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/links/{id}/delete', name: 'app_links_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, Request $request): Response
    {
        $shortUrl = $this->em->getRepository(ShortUrl::class)->find($id);

        if (!$shortUrl) {
            throw $this->createNotFoundException('Short URL not found');
        }

        $this->denyAccessUnlessGranted(ShortUrlVoter::DELETE, $shortUrl);
        $this->em->remove($shortUrl);
        $this->em->flush();

        $this->addFlash('success', 'Link deleted successfully!');

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{code}', name: 'app_redirect', methods: ['GET'], priority: -1)]
    public function redirectToUrl(
        string $code,
        ShortUrlRepository $repository,
        Request $request,
    ): Response {
        $shortUrl = $this->cache->get('short_url_'.$code, function (ItemInterface $item) use ($repository, $code) {
            $item->expiresAfter(3600);

            $shortUrl = $repository->findOneBy(['code' => $code]);

            if (!$shortUrl || !$shortUrl->getId()) {
                throw $this->createNotFoundException('Short URL not found');
            }

            return $shortUrl;
        });

        $this->bus->dispatch(new TrackClickMessage(
            $code,
            $request->getClientIp() ?? 'unknown',
            $request->headers->get('User-Agent'),
        ));

        return new RedirectResponse($shortUrl->getOriginalUrl(), Response::HTTP_FOUND);
    }
}
