<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('buyers-experience')]
class CurrencyRouteResponse extends StoreApiResponse
{
    /**
     * @var CurrencyCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(CurrencyCollection $currencies)
    {
        parent::__construct($currencies);
    }

    public function getCurrencies(): CurrencyCollection
    {
        return $this->object;
    }
}
