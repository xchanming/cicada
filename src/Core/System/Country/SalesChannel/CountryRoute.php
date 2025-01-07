<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\Event\CountryCriteriaEvent;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('fundamentals@discovery')]
class CountryRoute extends AbstractCountryRoute
{
    final public const ALL_TAG = 'country-route';

    /**
     * @internal
     *
     * @param SalesChannelRepository<CountryCollection> $countryRepository
     */
    public function __construct(
        private readonly SalesChannelRepository $countryRepository,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'country-route-' . $id;
    }

    #[Route(path: '/store-api/country', name: 'store-api.country', methods: ['GET', 'POST'], defaults: ['_entity' => 'country'])]
    public function load(Request $request, Criteria $criteria, SalesChannelContext $context): CountryRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(
            self::buildName($context->getSalesChannelId()),
            self::ALL_TAG
        ));

        $criteria->addFilter(new EqualsFilter('active', true));

        $this->dispatcher->dispatch(new CountryCriteriaEvent($request, $criteria, $context));
        $result = $this->countryRepository->search($criteria, $context);

        return new CountryRouteResponse($result);
    }

    protected function getDecorated(): AbstractCountryRoute
    {
        throw new DecorationPatternException(self::class);
    }
}
