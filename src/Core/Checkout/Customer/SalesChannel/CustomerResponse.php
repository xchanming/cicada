<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class CustomerResponse extends StoreApiResponse
{
    /**
     * @var CustomerEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(CustomerEntity $object)
    {
        parent::__construct($object);
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->object;
    }
}
