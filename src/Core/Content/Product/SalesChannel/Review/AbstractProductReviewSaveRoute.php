<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('after-sales')]
abstract class AbstractProductReviewSaveRoute
{
    abstract public function getDecorated(): AbstractProductReviewSaveRoute;

    abstract public function save(string $productId, RequestDataBag $data, SalesChannelContext $context): NoContentResponse;
}
