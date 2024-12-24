<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\InAppPurchases\Event;

use Cicada\Core\Framework\App\InAppPurchases\Gateway\InAppPurchasesGateway;
use Cicada\Core\Framework\App\InAppPurchases\Response\InAppPurchasesResponse;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched once a response is received from the app server after making a call to the
 * InAppPurchasesGateway.
 *
 * @internal
 *
 * @codeCoverageIgnore
 *
 * @see InAppPurchasesGateway::process() for an example implementation
 */
#[Package('checkout')]
class InAppPurchasesGatewayEvent extends Event
{
    public function __construct(
        public readonly InAppPurchasesResponse $response,
    ) {
    }
}
