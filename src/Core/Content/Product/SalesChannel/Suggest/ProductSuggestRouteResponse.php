<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Suggest;

use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('services-settings')]
class ProductSuggestRouteResponse extends StoreApiResponse
{
    /**
     * @var ProductListingResult
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function getListingResult(): ProductListingResult
    {
        return $this->object;
    }
}
