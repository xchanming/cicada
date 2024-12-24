<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('buyers-experience')]
class CountryStateRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<CountryStateCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<CountryStateCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getStates(): CountryStateCollection
    {
        return $this->object->getEntities();
    }
}
