<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture;

use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ForeignKey;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Entity as EntityStruct;

/**
 * @internal
 */
#[Entity('attribute_entity_agg', parent: 'attribute_entity', since: '6.6.3.0')]
class AttributeEntityAgg extends EntityStruct
{
    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[ForeignKey(entity: 'attribute_entity')]
    public string $attributeEntityId;

    #[Field(type: FieldType::STRING)]
    public string $number;
}
