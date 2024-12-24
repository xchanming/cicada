<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\Country\CountrySateDataPageletLoadedHook;
use Cicada\Storefront\Pagelet\Country\CountryStateDataPageletLoadedHook;
use Cicada\Storefront\Pagelet\Country\CountryStateDataPageletLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('buyers-experience')]
class CountryStateController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(private readonly CountryStateDataPageletLoader $countryStateDataPageletLoader)
    {
    }

    #[Route(path: 'country/country-state-data', name: 'frontend.country.country.data', defaults: ['XmlHttpRequest' => true, '_httpCache' => true], methods: ['POST'])]
    public function getCountryData(Request $request, SalesChannelContext $context): Response
    {
        $countryId = (string) $request->request->get('countryId');

        if (!$countryId) {
            throw RoutingException::missingRequestParameter('countryId');
        }

        $countryStateDataPagelet = $this->countryStateDataPageletLoader->load($countryId, $request, $context);

        $this->hook(new CountryStateDataPageletLoadedHook($countryStateDataPagelet, $context));

        Feature::callSilentIfInactive(
            'v6.7.0.0',
            fn () => $this->hook(new CountrySateDataPageletLoadedHook($countryStateDataPagelet, $context))
        );

        return new JsonResponse([
            'states' => $countryStateDataPagelet->getStates(),
        ]);
    }
}
