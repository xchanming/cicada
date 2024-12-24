<?php declare(strict_types=1);

namespace Cicada\Core\System\Tax;

use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Since;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleDefinition;

#[Package('checkout')]
class TaxDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'tax';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return TaxCollection::class;
    }

    public function getEntityClass(): string
    {
        return TaxEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    public function getDefaults(): array
    {
        return [
            'position' => 0,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FloatField('tax_rate', 'taxRate'))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new IntField('position', 'position'))->addFlags(new Required(), new Since('6.4.0.0'), new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
            (new OneToManyAssociationField('products', ProductDefinition::class, 'tax_id', 'id'))->addFlags(new RestrictDelete(), new ReverseInherited('tax')),
            (new OneToManyAssociationField('rules', TaxRuleDefinition::class, 'tax_id', 'id'))->addFlags(new RestrictDelete()),
            (new OneToManyAssociationField('shippingMethods', ShippingMethodDefinition::class, 'tax_id', 'id'))->addFlags(new RestrictDelete()),
        ]);
    }
}
