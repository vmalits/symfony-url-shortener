<?php

declare(strict_types=1);

namespace App\Domain\Click\Entity;

use App\Domain\Click\ValueObject\IpAddress;
use App\Domain\ShortUrl\Entity\ShortUrl;

class Click
{
    private ?int $id = null;
    private readonly string $ip;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly ShortUrl $shortUrl,
        IpAddress $ip,
        private readonly ?string $userAgent,
        private readonly ?string $country,
        private readonly ?string $referrer,
    ) {
        $this->ip = $ip->value();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortUrl(): ShortUrl
    {
        return $this->shortUrl;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
