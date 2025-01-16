<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Aggregate\FlowSequence;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<FlowSequenceEntity>
 */
#[Package('after-sales')]
class FlowSequenceCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'flow_sequence_collection';
    }

    protected function getExpectedClass(): string
    {
        return FlowSequenceEntity::class;
    }
}
