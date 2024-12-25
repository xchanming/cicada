<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @deprecated tag:v6.7.0 - will be removed, customer has no default payment method anymore
 */
#[Package('checkout')]
class CustomerChangedPaymentMethodEvent extends Event implements SalesChannelAware, CicadaSalesChannelEvent, CustomerAware, MailAware, FlowEventAware
{
    final public const EVENT_NAME = 'checkout.customer.changed-payment-method';

    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly CustomerEntity $customer,
        private readonly RequestDataBag $requestDataBag
    ) {
    }

    public function getName(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return self::EVENT_NAME;
    }

    public function getCustomer(): CustomerEntity
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->customer;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->salesChannelContext;
    }

    public function getSalesChannelId(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getContext(): Context
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->salesChannelContext->getContext();
    }

    public function getRequestDataBag(): RequestDataBag
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->requestDataBag;
    }

    public static function getAvailableData(): EventDataCollection
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class));
    }

    public function getCustomerId(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return $this->getCustomer()->getId();
    }

    public function getMailStruct(): MailRecipientStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        return new MailRecipientStruct(
            [
                $this->customer->getEmail() => $this->customer->getName(),
            ]
        );
    }
}
