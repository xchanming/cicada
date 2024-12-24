<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\AppScriptCondition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<AppScriptConditionEntity>
 */
#[Package('core')]
class AppScriptConditionCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'app_script_condition_collection';
    }

    protected function getExpectedClass(): string
    {
        return AppScriptConditionEntity::class;
    }
}
