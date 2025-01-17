<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Address\Error;

use Cicada\Core\Checkout\Cart\Error\ErrorRoute;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class BillingAddressSalutationMissingError extends SalutationMissingError
{
    protected const KEY = parent::KEY . '-billing-address';

    public function __construct(CustomerAddressEntity $address)
    {
        $this->message = \sprintf(
            'A salutation needs to be defined for the billing address "%s %s, %s".',
            $address->getName(),
            $address->getZipcode(),
            $address->getCity()?->getName()
        );

        $this->parameters = [
            'addressId' => $address->getId(),
        ];

        parent::__construct($this->message);
    }

    public function getId(): string
    {
        return self::KEY;
    }

    public function getRoute(): ?ErrorRoute
    {
        return new ErrorRoute(
            'frontend.account.address.edit.page',
            $this->parameters
        );
    }
}
