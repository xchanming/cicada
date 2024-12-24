<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Country;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Cicada\Storefront\Pagelet\Pagelet;

#[Package('storefront')]
class CountryStateDataPagelet extends Pagelet
{
    protected CountryStateCollection $states;

    public function __construct()
    {
        $this->states = new CountryStateCollection();
    }

    public function getStates(): CountryStateCollection
    {
        return $this->states;
    }

    public function setStates(CountryStateCollection $states): void
    {
        $this->states = $states;
    }
}
