<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
abstract class AbstractProductVariationBuilder
{
    abstract public function getDecorated(): AbstractProductVariationBuilder;

    abstract public function build(Entity $product): void;
}
