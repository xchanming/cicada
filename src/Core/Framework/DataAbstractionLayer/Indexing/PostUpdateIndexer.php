<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Indexing;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class PostUpdateIndexer extends EntityIndexer
{
    final public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        return null;
    }
}
