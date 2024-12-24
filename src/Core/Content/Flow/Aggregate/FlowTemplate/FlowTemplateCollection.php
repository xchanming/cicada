<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Aggregate\FlowTemplate;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<FlowTemplateEntity>
 */
#[Package('services-settings')]
class FlowTemplateCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'flow_template_collection';
    }

    protected function getExpectedClass(): string
    {
        return FlowTemplateEntity::class;
    }
}
