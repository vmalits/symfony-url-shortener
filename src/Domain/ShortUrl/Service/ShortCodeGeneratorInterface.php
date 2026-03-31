<?php

declare(strict_types=1);

namespace App\Domain\ShortUrl\Service;

interface ShortCodeGeneratorInterface
{
    public function generate(): string;
}
