<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderAddress;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Cicada\Core\System\Country\CountryCollection;

/**
 * @extends EntityCollection<OrderAddressEntity>
 */
#[Package('checkout')]
class OrderAddressCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getCountryIds(): array
    {
        return $this->fmap(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountryId());
    }

    public function filterByCountryId(string $id): self
    {
        return $this->filter(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountryId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getCountryStateIds(): array
    {
        return $this->fmap(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountryStateId());
    }

    public function filterByCountryStateId(string $id): self
    {
        return $this->filter(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountryStateId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getVatIds(): array
    {
        return $this->fmap(fn (OrderAddressEntity $orderAddress) => $orderAddress->getVatId());
    }

    public function filterByVatId(string $id): self
    {
        return $this->filter(fn (OrderAddressEntity $orderAddress) => $orderAddress->getVatId() === $id);
    }

    public function getCountries(): CountryCollection
    {
        return new CountryCollection(
            $this->fmap(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountry())
        );
    }

    public function getCountryStates(): CountryStateCollection
    {
        return new CountryStateCollection(
            $this->fmap(fn (OrderAddressEntity $orderAddress) => $orderAddress->getCountryState())
        );
    }

    public function getApiAlias(): string
    {
        return 'order_address_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderAddressEntity::class;
    }
}
