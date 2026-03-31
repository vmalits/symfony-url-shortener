<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

final class ShortUrlVoter implements CacheableVoterInterface
{
    public const string DELETE = 'delete';

    public function supportsAttribute(string $attribute): bool
    {
        return self::DELETE === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return ShortUrl::class === $subjectType || is_a($subjectType, ShortUrl::class, true);
    }

    /**
     * @param array<string> $attributes
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        if (!$subject instanceof ShortUrl) {
            return self::ACCESS_ABSTAIN;
        }

        $attribute = $attributes[0] ?? null;
        if (self::DELETE !== $attribute) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return self::ACCESS_DENIED;
        }

        return $subject->getUser()->getId() === $user->getId() ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
