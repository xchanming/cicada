<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Address\Detail;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Storefront\Page\Page;

#[Package('storefront')]
class AddressDetailPage extends Page
{
    /**
     * @var CustomerAddressEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $address;

    /**
     * @var SalutationCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salutations;

    /**
     * @var CountryCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $countries;

    public function getAddress(): ?CustomerAddressEntity
    {
        return $this->address;
    }

    public function setAddress(?CustomerAddressEntity $address): void
    {
        $this->address = $address;
    }

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): void
    {
        $this->salutations = $salutations;
    }

    public function getCountries(): CountryCollection
    {
        return $this->countries;
    }

    public function setCountries(CountryCollection $countries): void
    {
        $this->countries = $countries;
    }
}
