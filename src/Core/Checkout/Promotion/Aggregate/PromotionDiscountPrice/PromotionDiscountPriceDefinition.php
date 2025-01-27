<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice;

use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyDefinition;

#[Package('checkout')]
class PromotionDiscountPriceDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'promotion_discount_prices';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PromotionDiscountPriceEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PromotionDiscountPriceCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return PromotionDiscountDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('discount_id', 'discountId', PromotionDiscountDefinition::class))->addFlags(new Required()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new Required()),
            (new FloatField('price', 'price'))->addFlags(new Required()),
            new ManyToOneAssociationField('promotionDiscount', 'discount_id', PromotionDiscountDefinition::class, 'id', false),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id', false),
        ]);
    }
}
