<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\FlowEvent;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<AppFlowEventEntity>
 */
#[Package('core')]
class AppFlowEventCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'app_flow_event_collection';
    }

    protected function getExpectedClass(): string
    {
        return AppFlowEventEntity::class;
    }
}
