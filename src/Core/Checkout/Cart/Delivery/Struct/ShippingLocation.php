<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Delivery\Struct;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Cicada\Core\System\Country\CountryEntity;

#[Package('checkout')]
class ShippingLocation extends Struct
{
    /**
     * @var CountryEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $country;

    /**
     * @var CountryStateEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $state;

    /**
     * @var CustomerAddressEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $address;

    public function __construct(
        CountryEntity $country,
        ?CountryStateEntity $state,
        ?CustomerAddressEntity $address
    ) {
        $this->country = $country;
        $this->state = $state;
        $this->address = $address;
    }

    public static function createFromAddress(CustomerAddressEntity $address): self
    {
        \assert($address->getCountry() !== null);

        return new self(
            $address->getCountry(),
            $address->getCountryState(),
            $address
        );
    }

    public static function createFromCountry(CountryEntity $country): self
    {
        return new self($country, null, null);
    }

    public function getCountry(): CountryEntity
    {
        return $this->address?->getCountry() ?? $this->country;
    }

    public function getState(): ?CountryStateEntity
    {
        if ($this->address) {
            return $this->address->getCountryState();
        }

        return $this->state;
    }

    public function getAddress(): ?CustomerAddressEntity
    {
        return $this->address;
    }

    public function getApiAlias(): string
    {
        return 'cart_delivery_shipping_location';
    }
}
