<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\SalesChannel;

use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('inventory')]
class CategoryRouteResponse extends StoreApiResponse
{
    /**
     * @var CategoryEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(CategoryEntity $category)
    {
        parent::__construct($category);
    }

    public function getCategory(): CategoryEntity
    {
        return $this->object;
    }
}
