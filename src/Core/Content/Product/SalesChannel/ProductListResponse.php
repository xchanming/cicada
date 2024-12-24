<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('inventory')]
class ProductListResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<ProductCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<ProductCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getProducts(): ProductCollection
    {
        return $this->object->getEntities();
    }
}
