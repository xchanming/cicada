<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\Aggregate\ProductStreamFilter;

use Cicada\Core\Content\ProductStream\ProductStreamDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductStreamFilterDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'product_stream_filter';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductStreamFilterEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductStreamFilterCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    public function getHydratorClass(): string
    {
        return ProductStreamFilterHydrator::class;
    }

    protected function getParentDefinitionClass(): ?string
    {
        return ProductStreamDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class))->addFlags(new Required()),
            new ParentFkField(self::class),
            (new StringField('type', 'type'))->addFlags(new Required()),
            new StringField('field', 'field'),
            new StringField('operator', 'operator'),
            new LongTextField('value', 'value'),
            new JsonField('parameters', 'parameters'),
            new IntField('position', 'position'),
            new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class, 'id', false),
            new ParentAssociationField(self::class, 'id'),
            new ChildrenAssociationField(self::class, 'queries'),
            new CustomFields(),
        ]);
    }
}
