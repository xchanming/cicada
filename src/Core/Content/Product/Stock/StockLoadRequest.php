<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Stock;

use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class StockLoadRequest
{
    /**
     * @param array<string> $productIds
     */
    public function __construct(public array $productIds)
    {
    }
}
