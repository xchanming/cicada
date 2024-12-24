<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class FkFieldExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new FkField('test', 'test', ProductDefinition::class)
        );
        $collection->add(
            new ManyToOneAssociationField('testAssociation', 'test', ProductDefinition::class)
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
