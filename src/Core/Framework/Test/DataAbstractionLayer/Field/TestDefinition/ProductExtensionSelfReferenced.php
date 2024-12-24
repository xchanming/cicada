<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('inventory')]
class ProductExtensionSelfReferenced extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new FkField('linked_product_id', 'linkedProductId', ProductDefinition::class)
        );

        $collection->add(
            (new ReferenceVersionField(ProductDefinition::class, 'linked_product_version_id'))->addFlags(new ApiAware(), new Required())
        );

        $collection->add(
            new ManyToOneAssociationField('ManyToOneSelfReference', 'linked_product_id', ProductDefinition::class)
        );

        $collection->add(
            new ManyToOneAssociationField('ManyToOneSelfReferenceAutoload', 'linked_product_id', ProductDefinition::class, 'id', true)
        );

        $collection->add(
            new OneToManyAssociationField('oneToManySelfReferenced', ProductDefinition::class, 'linked_product_id', 'id')
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function getEntityName(): string
    {
        return 'product';
    }
}
