<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Hook;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiRequestHook;

/**
 * Triggered when PaymentMethodRoute is requested
 *
 * @hook-use-case data_loading
 *
 * @since 6.5.0.0
 *
 * @final
 */
#[Package('checkout')]
class PaymentMethodRouteHook extends StoreApiRequestHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'payment-method-route-request';

    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodCollection $collection,
        private readonly bool $onlyAvailable,
        protected SalesChannelContext $salesChannelContext
    ) {
        parent::__construct($salesChannelContext->getContext());
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getCollection(): PaymentMethodCollection
    {
        return $this->collection;
    }

    public function isOnlyAvailable(): bool
    {
        return $this->onlyAvailable;
    }
}
