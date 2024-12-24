<?php declare(strict_types=1);

namespace Cicada\Core\Content\Rule\Aggregate\RuleCondition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<RuleConditionEntity>
 */
#[Package('services-settings')]
class RuleConditionCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'rule_condition_collection';
    }

    protected function getExpectedClass(): string
    {
        return RuleConditionEntity::class;
    }
}
