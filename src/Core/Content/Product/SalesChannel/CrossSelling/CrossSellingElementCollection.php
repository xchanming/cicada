<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\CrossSelling;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<CrossSellingElement>
 */
#[Package('inventory')]
class CrossSellingElementCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'cross_selling_elements';
    }

    protected function getExpectedClass(): ?string
    {
        return CrossSellingElement::class;
    }
}
