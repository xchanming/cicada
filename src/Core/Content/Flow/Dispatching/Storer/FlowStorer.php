<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
abstract class FlowStorer
{
    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    abstract public function store(FlowEventAware $event, array $stored): array;

    abstract public function restore(StorableFlow $storable): void;
}
