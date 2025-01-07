<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\SalesChannel;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('discovery')]
class NavigationRouteResponse extends StoreApiResponse
{
    /**
     * @var CategoryCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(CategoryCollection $categories)
    {
        parent::__construct($categories);
    }

    public function getCategories(): CategoryCollection
    {
        return $this->object;
    }
}
