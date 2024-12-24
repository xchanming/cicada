<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Allows project overrides to change cheapest price selection
 */
#[Package('core')]
class CheapestPriceQuantitySelector extends AbstractCheapestPriceQuantitySelector
{
    public function getDecorated(): AbstractCheapestPriceQuantitySelector
    {
        throw new DecorationPatternException(self::class);
    }

    public function add(QueryBuilder $query): void
    {
        $query->addSelect([
            'price.quantity_start != 1 as is_ranged',
        ]);

        $query->andWhere('price.quantity_end IS NULL');
    }
}
