<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
class RecurringPaymentOrderCriteriaEvent extends Event
{
    public function __construct(
        private readonly string $orderId,
        private readonly Criteria $criteria,
        private readonly Context $context
    ) {
    }

    public function getOrderId(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The event `RecurringPaymentOrderCriteriaEvent` is deprecated and will be removed with new payment handlers.');

        return $this->orderId;
    }

    public function getCriteria(): Criteria
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The event `RecurringPaymentOrderCriteriaEvent` is deprecated and will be removed with new payment handlers.');

        return $this->criteria;
    }

    public function getContext(): Context
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The event `RecurringPaymentOrderCriteriaEvent` is deprecated and will be removed with new payment handlers.');

        return $this->context;
    }
}
