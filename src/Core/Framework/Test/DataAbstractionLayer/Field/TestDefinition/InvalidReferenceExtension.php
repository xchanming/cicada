<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class InvalidReferenceExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
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
