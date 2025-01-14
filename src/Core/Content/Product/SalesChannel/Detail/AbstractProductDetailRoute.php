<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Detail;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
abstract class AbstractProductDetailRoute
{
    abstract public function getDecorated(): AbstractProductDetailRoute;

    abstract public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductDetailRouteResponse;
}
