<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
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
        ReadOnlyPropertyRector::class => [
            __DIR__.'/src/Domain/ShortUrl/Entity/ShortUrl.php',
            __DIR__.'/src/Domain/User/Entity/User.php',
        ],
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
