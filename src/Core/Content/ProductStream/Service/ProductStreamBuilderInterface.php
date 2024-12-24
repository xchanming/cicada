<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\Service;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductStreamBuilderInterface
{
    /**
     * @return array<int, Filter>
     */
    public function buildFilters(
        string $id,
        Context $context
    ): array;
}
