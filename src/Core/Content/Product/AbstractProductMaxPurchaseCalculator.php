<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
abstract class AbstractProductMaxPurchaseCalculator
{
    abstract public function getDecorated(): AbstractProductMaxPurchaseCalculator;

    abstract public function calculate(Entity $product, SalesChannelContext $context): int;
}
