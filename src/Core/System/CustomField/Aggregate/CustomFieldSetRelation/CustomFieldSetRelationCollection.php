<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomField\Aggregate\CustomFieldSetRelation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CustomFieldSetRelationEntity>
 */
#[Package('services-settings')]
class CustomFieldSetRelationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'custom_field_set_relation_collection';
    }

    protected function getExpectedClass(): string
    {
        return CustomFieldSetRelationEntity::class;
    }
}
