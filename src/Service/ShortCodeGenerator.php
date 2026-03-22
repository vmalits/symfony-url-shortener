<?php

declare(strict_types=1);

namespace App\Service;

final class ShortCodeGenerator
{
    public function generate(): string
    {
        return substr(bin2hex(random_bytes(6)), 0, 10);
    }
}
