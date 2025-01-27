<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\SalesChannel;

use Cicada\Core\Checkout\Payment\Hook\PaymentMethodRouteHook;
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
class SortedPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractPaymentMethodRoute $decorated,
        private readonly ScriptExecutor $scriptExecutor
    ) {
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/payment-method', name: 'store-api.payment.method', methods: ['GET', 'POST'], defaults: ['_entity' => 'payment_method'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        if (Feature::isActive('cache_rework')) {
            return $this->getDecorated()->load($request, $context, $criteria);
        }

        $response = $this->getDecorated()->load($request, $context, $criteria);

        $response->getPaymentMethods()->sortPaymentMethodsByPreference($context);

        $this->scriptExecutor->execute(new PaymentMethodRouteHook(
            $response->getPaymentMethods(),
            $request->query->getBoolean('onlyAvailable') || $request->request->getBoolean('onlyAvailable'),
            $context
        ));

        return $response;
    }
}
