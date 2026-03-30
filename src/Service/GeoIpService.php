<?php

declare(strict_types=1);

namespace App\Service;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

final class GeoIpService implements GeoIpInterface
{
    private ?Reader $reader = null;

    public function __construct(
        private readonly string $dbPath = __DIR__.'/../../var/geoip/GeoLite2-Country.mmdb',
    ) {
    }

    public function getCountryCode(string $ip): ?string
    {
        if (!file_exists($this->dbPath)) {
            return null;
        }

        try {
            $this->reader ??= new Reader($this->dbPath);
            $record = $this->reader->country($ip);

            return $record->country->isoCode;
        } catch (AddressNotFoundException|\Exception) {
            return null;
        }
    }
}
