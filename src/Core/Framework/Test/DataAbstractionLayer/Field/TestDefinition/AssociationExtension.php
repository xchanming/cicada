<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class AssociationExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField('toMany', ExtendedDefinition::class, 'extendable_id'))
                ->addFlags(new ApiAware())
        );

        $collection->add(
            (new OneToOneAssociationField('toOne', 'id', 'extendable_id', ExtendedDefinition::class, false))
                ->addFlags(new ApiAware())
        );

        $collection->add(
            (new OneToOneAssociationField('toOneWithoutApiAware', 'id', 'extendable_id', ExtendedDefinition::class, false))
                ->removeFlag(ApiAware::class)
        );
    }

    public function getDefinitionClass(): string
    {
        return ExtendableDefinition::class;
    }

    public function getEntityName(): string
    {
        return 'extendable';
    }
}
