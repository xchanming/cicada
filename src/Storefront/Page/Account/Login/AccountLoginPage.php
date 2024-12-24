<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Login;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Storefront\Page\Page;

#[Package('checkout')]
class AccountLoginPage extends Page
{
    /**
     * @var CountryCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $countries;

    /**
     * @var SalutationCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salutations;

    public function getCountries(): CountryCollection
    {
        return $this->countries;
    }

    public function setCountries(CountryCollection $countries): void
    {
        $this->countries = $countries;
    }

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): void
    {
        $this->salutations = $salutations;
    }
}
