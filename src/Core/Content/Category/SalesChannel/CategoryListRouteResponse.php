<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\SalesChannel;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('discovery')]
class CategoryListRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<CategoryCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<CategoryCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getCategories(): CategoryCollection
    {
        return $this->object->getEntities();
    }
}
