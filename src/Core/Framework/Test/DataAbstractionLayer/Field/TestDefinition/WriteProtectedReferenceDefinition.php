<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

/**
 * @internal
 */
class WriteProtectedReferenceDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = '_test_nullable_reference';

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
            (new FkField('wp_id', 'wpId', WriteProtectedDefinition::class))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FkField('relation_id', 'relationId', WriteProtectedRelationDefinition::class))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('wp', 'wp_id', WriteProtectedDefinition::class, 'id', false),
            new ManyToOneAssociationField('relation', 'relation_id', WriteProtectedRelationDefinition::class, 'id', false),
        ]);
    }
}
