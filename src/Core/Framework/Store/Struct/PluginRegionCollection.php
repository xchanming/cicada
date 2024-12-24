<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @codeCoverageIgnore
 * Pseudo immutable collection
 *
 * @extends Collection<PluginRegionStruct>
 */
#[Package('checkout')]
final class PluginRegionCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'store_plugin_region_collection';
    }

    protected function getExpectedClass(): string
    {
        return PluginRegionStruct::class;
    }
}
