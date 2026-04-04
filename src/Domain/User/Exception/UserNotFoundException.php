<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public static function byId(int $id): self
    {
        return new self(sprintf('User with id "%d" not found.', $id));
    }

    public static function byEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" not found.', $email));
    }
}
