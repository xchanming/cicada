<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CustomerConfirmRegisterUrlEvent extends Event implements CicadaSalesChannelEvent
{
    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private string $confirmUrl,
        private readonly string $emailHash,
        private readonly ?string $customerHash,
        private readonly CustomerEntity $customer
    ) {
    }

    public function getConfirmUrl(): string
    {
        return $this->confirmUrl;
    }

    public function setConfirmUrl(string $confirmUrl): void
    {
        $this->confirmUrl = $confirmUrl;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannelId();
    }

    public function getEmailHash(): string
    {
        return $this->emailHash;
    }

    public function getCustomerHash(): ?string
    {
        return $this->customerHash;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }
}
