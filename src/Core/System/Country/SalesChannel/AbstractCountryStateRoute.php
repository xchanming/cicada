<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route can be used to load all states of a given country.
 * With this route it is also possible to send the standard API parameters such as: 'page', 'limit', 'filter', etc.
 */
#[Package('fundamentals@discovery')]
abstract class AbstractCountryStateRoute
{
    abstract public function load(string $countryId, Request $request, Criteria $criteria, SalesChannelContext $context): CountryStateRouteResponse;

    abstract protected function getDecorated(): AbstractCountryStateRoute;
}
