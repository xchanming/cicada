<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('fundamentals@discovery')]
class CountryRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<CountryCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<CountryCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    /**
     * @return EntitySearchResult<CountryCollection>
     */
    public function getResult(): EntitySearchResult
    {
        return $this->object;
    }

    public function getCountries(): CountryCollection
    {
        return $this->object->getEntities();
    }
}
