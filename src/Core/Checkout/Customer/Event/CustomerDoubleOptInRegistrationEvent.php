<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Flow\Dispatching\Action\FlowMailVariables;
use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CustomerDoubleOptInRegistrationEvent extends Event implements SalesChannelAware, CustomerAware, MailAware, ScalarValuesAware, FlowEventAware
{
    final public const EVENT_NAME = 'checkout.customer.double_opt_in_registration';

    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private readonly CustomerEntity $customer,
        private readonly SalesChannelContext $salesChannelContext,
        private readonly string $confirmUrl
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('confirmUrl', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->customer->getEmail() => $this->customer->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    /**
     * @return array<string, scalar|array<mixed>|null>
     */
    public function getValues(): array
    {
        return [FlowMailVariables::CONFIRM_URL => $this->confirmUrl];
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getConfirmUrl(): string
    {
        return $this->confirmUrl;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannelId();
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getCustomerId(): string
    {
        return $this->customer->getId();
    }
}
