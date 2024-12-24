<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cart;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
interface ProductGatewayInterface
{
    public function get(array $ids, SalesChannelContext $context): ProductCollection;
}
