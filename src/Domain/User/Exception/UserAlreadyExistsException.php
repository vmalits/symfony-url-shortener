<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserAlreadyExistsException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('A user with email "%s" already exists.', $email));
    }
}
