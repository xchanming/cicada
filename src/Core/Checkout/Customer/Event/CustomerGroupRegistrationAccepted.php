<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\CustomerGroupAware;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CustomerGroupRegistrationAccepted extends Event implements SalesChannelAware, CustomerAware, MailAware, CustomerGroupAware, FlowEventAware
{
    final public const EVENT_NAME = 'customer.group.registration.accepted';

    /**
     * @internal
     */
    public function __construct(
        private readonly CustomerEntity $customer,
        private readonly CustomerGroupEntity $customerGroup,
        private readonly Context $context,
        private readonly ?MailRecipientStruct $mailRecipientStruct = null
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('customerGroup', new EntityType(CustomerGroupDefinition::class));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if ($this->mailRecipientStruct) {
            return $this->mailRecipientStruct;
        }

        return new MailRecipientStruct(
            [
                $this->customer->getEmail() => $this->customer->getName(),
            ]
        );
    }

    public function getSalesChannelId(): string
    {
        return $this->customer->getSalesChannelId();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getCustomerGroup(): CustomerGroupEntity
    {
        return $this->customerGroup;
    }

    public function getCustomerId(): string
    {
        return $this->getCustomer()->getId();
    }

    public function getCustomerGroupId(): string
    {
        return $this->getCustomerGroup()->getId();
    }
}
