<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Search;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used for the product search in the search pages
 */
#[Package('services-settings')]
abstract class AbstractProductSearchRoute
{
    abstract public function getDecorated(): AbstractProductSearchRoute;

    abstract public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse;
}
