<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\ShortUrl\Entity\ShortUrl;
use App\Domain\User\ValueObject\Email;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id = null;
    /** @var Collection<int, ShortUrl> */
    private Collection $shortUrls;
    private readonly \DateTimeImmutable $createdAt;

    /**
     * @param list<string> $roles
     */
    private function __construct(
        private readonly string $email,
        private string $password,
        private readonly array $roles = [],
    ) {
        $this->shortUrls = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(Email $email, string $hashedPassword): self
    {
        return new self($email->value(), $hashedPassword);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('User email cannot be empty.');
        }

        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
