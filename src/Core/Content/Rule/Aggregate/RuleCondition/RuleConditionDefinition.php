<?php declare(strict_types=1);

namespace Cicada\Core\Content\Rule\Aggregate\RuleCondition;

use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class RuleConditionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'rule_condition';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return RuleConditionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RuleConditionCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return RuleDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('type', 'type'))->addFlags(new Required()),
            (new FkField('rule_id', 'ruleId', RuleDefinition::class))->addFlags(new Required()),
            new FkField('script_id', 'scriptId', AppScriptConditionDefinition::class),
            new ParentFkField(self::class),
            new JsonField('value', 'value'),
            new IntField('position', 'position'),
            new ManyToOneAssociationField('rule', 'rule_id', RuleDefinition::class, 'id'),
            new ManyToOneAssociationField('appScriptCondition', 'script_id', AppScriptConditionDefinition::class, 'id', !Feature::isActive('v6.7.0.0')),
            new ParentAssociationField(self::class, 'id'),
            new ChildrenAssociationField(self::class),
            new CustomFields(),
        ]);
    }
}
