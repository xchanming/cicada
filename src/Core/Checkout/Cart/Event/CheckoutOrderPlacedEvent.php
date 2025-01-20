<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\CustomerGroupAware;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAware;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CheckoutOrderPlacedEvent extends Event implements SalesChannelAware, SalesChannelContextAware, OrderAware, MailAware, CustomerAware, CustomerGroupAware, FlowEventAware
{
    final public const EVENT_NAME = 'checkout.order.placed';

    /**
     * @deprecated tag:v6.7.0 - Parameter $context will be type of SalesChannelContext and readonly
     * @deprecated tag:v6.7.0 - Parameter $salesChannelId will be removed
     */
    public function __construct(
        private Context|SalesChannelContext $context,
        private readonly OrderEntity $order,
        private readonly string $salesChannelId = '',
        private ?MailRecipientStruct $mailRecipientStruct = null
    ) {
        if ($context instanceof Context) {
            Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The parameter $context will be type of SalesChannelContext');

            if (!$salesChannelId) {
                Feature::throwException('v6.7.0.0', 'The parameter $salesChannelId is required when passing Context');
            }
        }
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class));
    }

    public function getContext(): Context
    {
        /**
         * @deprecated tag:v6.7.0 - Will be removed
         */
        if ($this->context instanceof Context) {
            return $this->context;
        }

        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        if ($this->context instanceof Context) {
            throw CartException::missingSalesChannelContext();
        }

        return $this->context;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->order->getOrderCustomer()?->getEmail() => $this->order->getOrderCustomer()?->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        if ($this->context instanceof Context) {
            return $this->salesChannelId;
        }

        return $this->context->getSalesChannelId();
    }

    public function getCustomerId(): string
    {
        $customerId = $this->getOrder()->getOrderCustomer()?->getCustomerId();

        if (!$customerId) {
            throw CartException::orderCustomerDeleted($this->getOrderId());
        }

        return $customerId;
    }

    public function getCustomerGroupId(): string
    {
        /**
         * @deprecated tag:v6.7.0 - Will be removed
         */
        if ($this->context instanceof Context) {
            $customerGroupId = $this->order->getOrderCustomer()?->getCustomer()?->getGroupId();

            if (!$customerGroupId) {
                throw CartException::orderCustomerDeleted($this->order->getId());
            }

            return $customerGroupId;
        }

        return $this->context->getCustomerGroupId();
    }
}
