<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<FlowActionDefinition>
 */
#[Package('services-settings')]
class FlowActionCollectorResponse extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return FlowActionDefinition::class;
    }
}
