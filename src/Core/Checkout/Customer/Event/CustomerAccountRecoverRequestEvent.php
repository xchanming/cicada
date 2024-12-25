<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryEntity;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Flow\Dispatching\Action\FlowMailVariables;
use Cicada\Core\Content\Flow\Dispatching\Aware\CustomerRecoveryAware;
use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
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
class CustomerAccountRecoverRequestEvent extends Event implements SalesChannelAware, CicadaSalesChannelEvent, CustomerAware, MailAware, CustomerRecoveryAware, ScalarValuesAware, FlowEventAware
{
    public const EVENT_NAME = 'customer.recovery.request';

    private string $shopName;

    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private SalesChannelContext $salesChannelContext,
        private CustomerRecoveryEntity $customerRecovery,
        private string $resetUrl
    ) {
        $this->shopName = $salesChannelContext->getSalesChannel()->getTranslation('name');
    }

    /**
     * @return array<string, scalar|array<mixed>|null>
     */
    public function getValues(): array
    {
        return [
            FlowMailVariables::RESET_URL => $this->resetUrl,
            FlowMailVariables::SHOP_NAME => $this->shopName,
        ];
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getCustomerRecovery(): CustomerRecoveryEntity
    {
        return $this->customerRecovery;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customerRecovery', new EntityType(CustomerRecoveryDefinition::class))
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('resetUrl', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('shopName', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $customer = $this->customerRecovery->getCustomer();
            \assert($customer instanceof CustomerEntity);

            $this->mailRecipientStruct = new MailRecipientStruct([
                $customer->getEmail() => $customer->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getResetUrl(): string
    {
        return $this->resetUrl;
    }

    public function getShopName(): string
    {
        return $this->shopName;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customerRecovery->getCustomer();
    }

    public function getCustomerId(): string
    {
        return $this->getCustomerRecovery()->getCustomerId();
    }

    public function getCustomerRecoveryId(): string
    {
        return $this->customerRecovery->getId();
    }
}
