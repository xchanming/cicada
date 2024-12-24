<?php declare(strict_types=1);

namespace Cicada\Core\Content\Rule;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionCartRule\PromotionCartRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountRule\PromotionDiscountRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionOrderRule\PromotionOrderRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionPersonaRule\PromotionPersonaRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroup\PromotionSetGroupDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroupRule\PromotionSetGroupRuleDefinition;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceDefinition;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition;
use Cicada\Core\Content\Rule\Aggregate\RuleTag\RuleTagDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Since;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tag\TagDefinition;
use Cicada\Core\System\TaxProvider\TaxProviderDefinition;

#[Package('services-settings')]
class RuleDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'rule';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return RuleCollection::class;
    }

    public function getEntityClass(): string
    {
        return RuleEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new IntField('priority', 'priority'))->addFlags(new Required()),
            (new LongTextField('description', 'description'))->addFlags(new ApiAware()),
            (new BlobField('payload', 'payload'))->removeFlag(ApiAware::class)->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            (new BoolField('invalid', 'invalid'))->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            (new ListField('areas', 'areas'))->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            (new CustomFields())->addFlags(new ApiAware()),
            new JsonField('module_types', 'moduleTypes'),

            (new OneToManyAssociationField('conditions', RuleConditionDefinition::class, 'rule_id', 'id'))->addFlags(new CascadeDelete()),

            // Reverse Associations not available in sales-channel-api
            (new OneToManyAssociationField('productPrices', ProductPriceDefinition::class, 'rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PRODUCT_AREA)),
            (new OneToManyAssociationField('shippingMethodPrices', ShippingMethodPriceDefinition::class, 'rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::SHIPPING_AREA)),
            (new OneToManyAssociationField('shippingMethodPriceCalculations', ShippingMethodPriceDefinition::class, 'calculation_rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::SHIPPING_AREA)),
            (new OneToManyAssociationField('shippingMethods', ShippingMethodDefinition::class, 'availability_rule_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::SHIPPING_AREA)),
            (new OneToManyAssociationField('paymentMethods', PaymentMethodDefinition::class, 'availability_rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PAYMENT_AREA)),
            (new OneToManyAssociationField('personaPromotions', PromotionDefinition::class, 'persona_rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
            (new OneToManyAssociationField('flowSequences', FlowSequenceDefinition::class, 'rule_id', 'id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::FLOW_AREA)),
            (new OneToManyAssociationField('taxProviders', TaxProviderDefinition::class, 'availability_rule_id', 'id'))->addFlags(new RestrictDelete(), new Since('6.5.0.0')),

            new ManyToManyAssociationField('tags', TagDefinition::class, RuleTagDefinition::class, 'rule_id', 'tag_id'),

            // Promotion References
            (new ManyToManyAssociationField('personaPromotions', PromotionDefinition::class, PromotionPersonaRuleDefinition::class, 'rule_id', 'promotion_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
            (new ManyToManyAssociationField('orderPromotions', PromotionDefinition::class, PromotionOrderRuleDefinition::class, 'rule_id', 'promotion_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
            (new ManyToManyAssociationField('cartPromotions', PromotionDefinition::class, PromotionCartRuleDefinition::class, 'rule_id', 'promotion_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
            (new ManyToManyAssociationField('promotionDiscounts', PromotionDiscountDefinition::class, PromotionDiscountRuleDefinition::class, 'rule_id', 'discount_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
            (new ManyToManyAssociationField('promotionSetGroups', PromotionSetGroupDefinition::class, PromotionSetGroupRuleDefinition::class, 'rule_id', 'setgroup_id'))->addFlags(new RestrictDelete(), new RuleAreas(RuleAreas::PROMOTION_AREA)),
        ]);
    }
}
