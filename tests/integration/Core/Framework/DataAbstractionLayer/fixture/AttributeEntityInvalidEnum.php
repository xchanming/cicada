<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture;

use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Entity as EntityStruct;

/**
 * @internal
 */
#[Entity('attribute_entity_invalid_enum', since: '6.6.10.0')]
class AttributeEntityInvalidEnum extends EntityStruct
{
    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[Field(type: FieldType::ENUM)]
    public string $enum;
}
