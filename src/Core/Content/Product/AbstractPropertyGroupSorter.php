<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
abstract class AbstractPropertyGroupSorter
{
    abstract public function getDecorated(): AbstractPropertyGroupSorter;

    /**
     * @param EntityCollection<PropertyGroupOptionEntity|PartialEntity> $options
     */
    abstract public function sort(EntityCollection $options): PropertyGroupCollection;
}
