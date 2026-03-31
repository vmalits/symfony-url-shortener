<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface RecordsEvents
{
    /**
     * @return list<object>
     */
    public function releaseEvents(): array;
}
