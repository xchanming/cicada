<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class ToManyAssociationDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = '_test_to_many_association';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            new ManyToManyAssociationField('toMany', ToManyAssociationDependencyDefinition::class, ToManyAssociationMappingDefinition::class, 'id', 'to_many_id'),
        ]);
    }
}
