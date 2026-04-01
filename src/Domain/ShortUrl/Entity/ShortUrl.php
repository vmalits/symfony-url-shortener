<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Entity;

use App\Domain\Click\Entity\Click;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ShortUrl
{
    private ?int $id = null;
    private readonly \DateTimeImmutable $createdAt;

    /** @var Collection<int, Click> */
    private Collection $clicks;

    private function __construct(
        private readonly string $originalUrl,
        private readonly string $code,
        private readonly User $user,
    ) {
        $this->clicks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(Url $url, ShortCode $code, User $user): self
    {
        return new self($url->value(), $code->value(), $user);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array<int, Click>
     */
    public function getClicks(): array
    {
        return $this->clicks->toArray();
    }

    public function belongsTo(User $user): bool
    {
        return $this->user->getId() === $user->getId();
    }

    public function recordClick(string $ip, ?string $userAgent, ?string $country, ?string $referrer): Click
    {
        $click = new Click($this, $ip, $userAgent, $country, $referrer);
        $this->clicks->add($click);

        return $click;
    }
}
