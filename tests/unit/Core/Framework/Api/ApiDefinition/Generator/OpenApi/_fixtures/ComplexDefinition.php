<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator\OpenApi\_fixtures;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator\_fixtures\SimpleDefinition;

/**
 * @internal
 */
class ComplexDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'complex';

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
        return new FieldCollection(
            [
                (new IdField('id_field', 'idField'))->addFlags(new ApiAware()),
                new ManyToOneAssociationField('simpleTo', 'simpleToId', SimpleDefinition::class),
                new OneToManyAssociationField('simpleManys', SimpleDefinition::class, 'ref_field'),
            ]
        );
    }
}
