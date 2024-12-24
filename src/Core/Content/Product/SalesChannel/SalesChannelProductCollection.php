<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class SalesChannelProductCollection extends ProductCollection
{
    protected function getExpectedClass(): string
    {
        return SalesChannelProductEntity::class;
    }
}
