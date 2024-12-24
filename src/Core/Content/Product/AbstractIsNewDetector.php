<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
abstract class AbstractIsNewDetector
{
    abstract public function getDecorated(): AbstractIsNewDetector;

    abstract public function isNew(Entity $product, SalesChannelContext $context): bool;
}
