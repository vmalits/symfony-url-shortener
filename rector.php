<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()

    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])

    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/var',
    ])

    ->withPhpSets(php84: true)

    ->withSets([
        LevelSetList::UP_TO_PHP_84,
        SymfonySetList::SYMFONY_80,
    ])

    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1)
    ->withCodeQualityLevel(1)

    ->withCache(__DIR__.'/var/cache/rector');
