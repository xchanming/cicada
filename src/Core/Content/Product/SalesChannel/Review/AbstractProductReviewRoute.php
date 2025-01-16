<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('after-sales')]
abstract class AbstractProductReviewRoute
{
    abstract public function getDecorated(): AbstractProductReviewRoute;

    abstract public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductReviewRouteResponse;
}
