<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency\Aggregate\CurrencyCountryRounding;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CurrencyCountryRoundingEntity>
 */
#[Package('buyers-experience')]
class CurrencyCountryRoundingCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'currency_country_rounding_collection';
    }

    protected function getExpectedClass(): string
    {
        return CurrencyCountryRoundingEntity::class;
    }
}
