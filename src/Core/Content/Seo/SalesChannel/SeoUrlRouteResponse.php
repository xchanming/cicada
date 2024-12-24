<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SalesChannel;

use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('buyers-experience')]
class SeoUrlRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<SeoUrlCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<SeoUrlCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getSeoUrls(): SeoUrlCollection
    {
        return $this->object->getEntities();
    }
}
