<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Suggest;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used for the product suggest in the page header
 */
#[Package('services-settings')]
abstract class AbstractProductSuggestRoute
{
    abstract public function getDecorated(): AbstractProductSuggestRoute;

    abstract public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSuggestRouteResponse;
}
