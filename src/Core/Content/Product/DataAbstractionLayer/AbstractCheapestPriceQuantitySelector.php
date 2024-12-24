<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Query\QueryBuilder;

#[Package('core')]
abstract class AbstractCheapestPriceQuantitySelector
{
    abstract public function getDecorated(): AbstractCheapestPriceQuantitySelector;

    abstract public function add(QueryBuilder $query): void;
}
