<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Listing;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('inventory')]
class ProductListingRouteResponse extends StoreApiResponse
{
    /**
     * @var ProductListingResult
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(ProductListingResult $object)
    {
        parent::__construct($object);
    }

    public function getResult(): ProductListingResult
    {
        return $this->object;
    }
}
