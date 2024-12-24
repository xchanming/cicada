<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PluginEntity>
 */
#[Package('core')]
class PluginCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'plugin_collection';
    }

    protected function getExpectedClass(): string
    {
        return PluginEntity::class;
    }
}
