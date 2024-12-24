<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Detail;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
abstract class AbstractAvailableCombinationLoader
{
    abstract public function getDecorated(): AbstractAvailableCombinationLoader;

    abstract public function loadCombinations(string $productId, SalesChannelContext $salesChannelContext): AvailableCombinationResult;
}
