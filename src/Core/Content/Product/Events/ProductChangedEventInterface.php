<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductChangedEventInterface
{
    /**
     * @return list<string>
     */
    public function getIds(): array;
}
