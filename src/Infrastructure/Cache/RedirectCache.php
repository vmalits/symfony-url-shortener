<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class RedirectCache
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getOriginalUrl(string $code, ShortUrlRepositoryInterface $repository): ?string
    {
        return $this->cache->get('short_url_'.$code, static function (ItemInterface $item) use ($repository, $code): ?string {
            $item->expiresAfter(3600);
            $shortUrl = $repository->findByCode($code);

            return $shortUrl?->getOriginalUrl();
        });
    }

    public function invalidate(string $code): void
    {
        $this->cache->delete('short_url_'.$code);
    }
}
