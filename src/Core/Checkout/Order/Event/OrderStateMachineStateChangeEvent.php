<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Event;

use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Content\MailTemplate\Exception\MailEventConfigurationException;
use Cicada\Core\Framework\Context;
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
class OrderStateMachineStateChangeEvent extends Event implements SalesChannelAware, OrderAware, MailAware, CustomerAware, FlowEventAware
{
    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private readonly string $name,
        private readonly OrderEntity $order,
        private readonly Context $context
    ) {
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class));
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $orderCustomer = $this->order->getOrderCustomer();
            if (!$orderCustomer) {
                throw new MailEventConfigurationException('Data for mailRecipientStruct not available.', self::class);
            }

            $this->mailRecipientStruct = new MailRecipientStruct([
                $orderCustomer->getEmail() => $orderCustomer->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->order->getSalesChannelId();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    public function getCustomerId(): string
    {
        $orderCustomer = $this->order->getOrderCustomer();

        if (!$orderCustomer?->getCustomerId()) {
            throw OrderException::orderCustomerDeleted($this->order->getId());
        }

        return $orderCustomer->getCustomerId();
    }
}
