<?php

declare(strict_types=1);

namespace App\Domain\Click\Service;

interface GeoIpInterface
{
    public function getCountryCode(string $ip): ?string;
}
