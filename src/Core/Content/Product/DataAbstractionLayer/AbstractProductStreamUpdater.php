<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class AbstractProductStreamUpdater extends EntityIndexer
{
    /**
     * @param array<string> $ids
     */
    abstract public function updateProducts(array $ids, Context $context): void;
}
