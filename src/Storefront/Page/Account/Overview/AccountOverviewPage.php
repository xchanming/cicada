<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Overview;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;
use Cicada\Storefront\Pagelet\Newsletter\Account\NewsletterAccountPagelet;

#[Package('checkout')]
class AccountOverviewPage extends Page
{
    /**
     * @var OrderEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $newestOrder;

    /**
     * @var CustomerEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $customer;

    protected NewsletterAccountPagelet $newsletterAccountPagelet;

    public function setNewestOrder(OrderEntity $order): void
    {
        $this->newestOrder = $order;
    }

    public function getNewestOrder(): ?OrderEntity
    {
        return $this->newestOrder;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getNewsletterAccountPagelet(): NewsletterAccountPagelet
    {
        return $this->newsletterAccountPagelet;
    }

    public function setNewsletterAccountPagelet(NewsletterAccountPagelet $newsletterAccountPagelet): void
    {
        $this->newsletterAccountPagelet = $newsletterAccountPagelet;
    }
}
