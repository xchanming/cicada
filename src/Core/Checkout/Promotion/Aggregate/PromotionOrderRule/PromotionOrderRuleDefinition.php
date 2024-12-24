<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionOrderRule;

use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class PromotionOrderRuleDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'promotion_order_rule';

    /**
     * This class is used as m:n relation between promotions and order rules.
     * It gives the option to assign what rules may be used for order conditions.
     */
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
        return new FieldCollection([
            (new FkField('promotion_id', 'promotionId', PromotionDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('rule_id', 'ruleId', RuleDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('promotion', 'promotion_id', PromotionDefinition::class, 'id'),
            new ManyToOneAssociationField('rule', 'rule_id', RuleDefinition::class, 'id'),
        ]);
    }
}
