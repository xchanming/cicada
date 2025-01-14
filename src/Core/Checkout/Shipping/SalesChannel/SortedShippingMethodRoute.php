<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping\SalesChannel;

use Cicada\Core\Checkout\Shipping\Hook\ShippingMethodRouteHook;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @deprecated tag:v6.7.0 - reason:decoration-will-be-removed - Will be removed
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class SortedShippingMethodRoute extends AbstractShippingMethodRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractShippingMethodRoute $decorated,
        private readonly ScriptExecutor $scriptExecutor
    ) {
    }

    public function getDecorated(): AbstractShippingMethodRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/shipping-method', name: 'store-api.shipping.method', methods: ['GET', 'POST'], defaults: ['_entity' => 'shipping_method'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ShippingMethodRouteResponse
    {
        if (Feature::isActive('cache_rework')) {
            return $this->getDecorated()->load($request, $context, $criteria);
        }

        $response = $this->getDecorated()->load($request, $context, $criteria);

        $response->getShippingMethods()->sortShippingMethodsByPreference($context);

        $this->scriptExecutor->execute(new ShippingMethodRouteHook(
            $response->getShippingMethods(),
            $request->query->getBoolean('onlyAvailable') || $request->request->getBoolean('onlyAvailable'),
            $context
        ));

        return $response;
    }
}
