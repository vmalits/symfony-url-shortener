<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Entity;

use App\Domain\Click\Entity\Click;
use App\Domain\Shared\RecordsEvents;
use App\Domain\ShortUrl\Event\ShortUrlCreatedEvent;
use App\Domain\ShortUrl\Event\ShortUrlVisitedEvent;
use App\Domain\ShortUrl\ValueObject\ShortCode;
use App\Domain\ShortUrl\ValueObject\Url;
use App\Domain\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ShortUrl implements RecordsEvents
{
    private ?int $id = null;
    private readonly \DateTimeImmutable $createdAt;

    /** @var Collection<int, Click> */
    private Collection $clicks;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly string $originalUrl,
        private readonly string $code,
        private readonly User $user,
    ) {
        $this->clicks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->recordEvent(new ShortUrlCreatedEvent(
            $this->code,
            $this->originalUrl,
            $this->user->getId(),
        ));
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

        $this->recordEvent(new ShortUrlVisitedEvent($this->code, $ip, $userAgent, $referrer));

        return $click;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
