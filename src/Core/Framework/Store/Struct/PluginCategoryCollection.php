<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @codeCoverageIgnore
 * Pseudo immutable collection
 *
 * @extends Collection<PluginCategoryStruct>
 */
#[Package('checkout')]
final class PluginCategoryCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'store_category_collection';
    }

    protected function getExpectedClass(): string
    {
        return PluginCategoryStruct::class;
    }
}
