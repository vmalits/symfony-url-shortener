<?php

declare(strict_types=1);

namespace App\Service;

interface GeoIpInterface
{
    public function getCountryCode(string $ip): ?string;
}
