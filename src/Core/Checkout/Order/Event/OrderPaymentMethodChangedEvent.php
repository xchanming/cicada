<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Event;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Content\Flow\Dispatching\Aware\OrderTransactionAware;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\AssociationNotFoundException;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class OrderPaymentMethodChangedEvent extends Event implements SalesChannelAware, OrderAware, CustomerAware, MailAware, OrderTransactionAware, FlowEventAware
{
    final public const EVENT_NAME = 'checkout.order.payment_method.changed';

    public function __construct(
        private readonly OrderEntity $order,
        private readonly OrderTransactionEntity $orderTransaction,
        private readonly Context $context,
        private readonly string $salesChannelId,
        private ?MailRecipientStruct $mailRecipientStruct = null
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $orderCustomer = $this->order->getOrderCustomer();
            if ($orderCustomer === null) {
                throw new AssociationNotFoundException('orderCustomer');
            }

            $this->mailRecipientStruct = new MailRecipientStruct([
                $orderCustomer->getEmail() => $orderCustomer->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class))
            ->add('orderTransaction', new EntityType(OrderTransactionDefinition::class));
    }

    public function getCustomerId(): string
    {
        $customer = $this->getOrder()->getOrderCustomer();

        if ($customer === null || $customer->getCustomerId() === null) {
            throw OrderException::orderCustomerDeleted($this->getOrderId());
        }

        return $customer->getCustomerId();
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransaction->getId();
    }
}
