<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\ShortUrl\Service\ShortCodeGeneratorInterface;

final class ShortCodeGenerator implements ShortCodeGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(5));
    }
}
