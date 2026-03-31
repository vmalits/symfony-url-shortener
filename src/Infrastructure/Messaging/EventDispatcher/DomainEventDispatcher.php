<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\EventDispatcher;

use App\Domain\Shared\RecordsEvents;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postFlush)]
final class DomainEventDispatcher
{
    /** @var list<RecordsEvents> */
    private array $trackedAggregates = [];

    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->trackedAggregates as $aggregate) {
            foreach ($aggregate->releaseEvents() as $event) {
                $this->eventBus->dispatch($event);
            }
        }

        $this->trackedAggregates = [];
    }

    public function track(RecordsEvents $aggregate): void
    {
        $this->trackedAggregates[] = $aggregate;
    }
}
