<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class ReferenceVersionExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new ManyToOneAssociationField('toOne', 'to_one', ExtendedDefinition::class)
        );

        $collection->add(
            new ReferenceVersionField(ExtendedDefinition::class)
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
