<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Listing;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used for the product listing in the cms pages
 */
#[Package('inventory')]
abstract class AbstractProductListingRoute
{
    abstract public function getDecorated(): AbstractProductListingRoute;

    abstract public function load(string $categoryId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductListingRouteResponse;
}
