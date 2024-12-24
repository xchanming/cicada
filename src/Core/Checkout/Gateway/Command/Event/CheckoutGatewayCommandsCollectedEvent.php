<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Hook\CartAware;
use Cicada\Core\Checkout\Gateway\Command\CheckoutGatewayCommandCollection;
use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched after the app checkout gateway has processed all active apps with checkout gateway.
 * It can be used to add custom commands, which should be executed after the checkout gateway has processed all apps.
 *
 * @see AppCheckoutGateway::process() for an example implementation
 */
#[Package('checkout')]
class CheckoutGatewayCommandsCollectedEvent extends Event implements CartAware
{
    public function __construct(
        private readonly CheckoutGatewayPayloadStruct $payload,
        private readonly CheckoutGatewayCommandCollection $commands,
    ) {
    }

    public function getPayload(): CheckoutGatewayPayloadStruct
    {
        return $this->payload;
    }

    public function getCommands(): CheckoutGatewayCommandCollection
    {
        return $this->commands;
    }

    public function getCart(): Cart
    {
        return $this->payload->getCart();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->payload->getSalesChannelContext();
    }
}
