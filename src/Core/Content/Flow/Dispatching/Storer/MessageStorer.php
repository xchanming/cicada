<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\Aware\MessageAware;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class MessageStorer extends FlowStorer
{
    /**
     * @param array<mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof MessageAware || isset($stored[MessageAware::MESSAGE])) {
            return $stored;
        }

        $stored[MessageAware::MESSAGE] = \serialize($event->getMessage());

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(MessageAware::MESSAGE)) {
            return;
        }

        $mail = \unserialize($storable->getStore(MessageAware::MESSAGE));

        $storable->setData(MessageAware::MESSAGE, $mail);
    }
}
