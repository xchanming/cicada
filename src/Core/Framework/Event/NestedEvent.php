<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
abstract class NestedEvent extends Event implements CicadaEvent
{
    use JsonSerializableTrait;

    public function getEvents(): ?NestedEventCollection
    {
        return null;
    }

    public function getFlatEventList(): NestedEventCollection
    {
        $events = [$this];

        if (!$nestedEvents = $this->getEvents()) {
            return new NestedEventCollection($events);
        }

        foreach ($nestedEvents as $event) {
            $events[] = $event;
            foreach ($event->getFlatEventList() as $item) {
                $events[] = $item;
            }
        }

        return new NestedEventCollection($events);
    }
}
