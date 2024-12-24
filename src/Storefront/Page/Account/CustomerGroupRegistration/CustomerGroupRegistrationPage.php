<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\CustomerGroupRegistration;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Account\Login\AccountLoginPage;

#[Package('checkout')]
class CustomerGroupRegistrationPage extends AccountLoginPage
{
    /**
     * @var CustomerGroupEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $customerGroup;

    public function setGroup(CustomerGroupEntity $customerGroup): void
    {
        $this->customerGroup = $customerGroup;
    }

    public function getGroup(): CustomerGroupEntity
    {
        return $this->customerGroup;
    }
}
