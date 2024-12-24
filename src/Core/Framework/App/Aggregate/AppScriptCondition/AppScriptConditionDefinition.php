<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\AppScriptCondition;

use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition;
use Cicada\Core\Framework\App\Aggregate\AppScriptConditionTranslation\AppScriptConditionTranslationDefinition;
use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AppScriptConditionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'app_script_condition';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AppScriptConditionCollection::class;
    }

    public function getEntityClass(): string
    {
        return AppScriptConditionEntity::class;
    }

    public function since(): ?string
    {
        return '6.4.10.3';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return AppDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('identifier', 'identifier'))->addFlags(new Required()),
            new TranslatedField('name'),
            (new BoolField('active', 'active'))->addFlags(new Required()),
            new StringField('group', 'group'),
            (new LongTextField('script', 'script'))->addFlags(new AllowHtml(false)),
            (new BlobField('constraints', 'constraints'))->removeFlag(ApiAware::class)->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            new JsonField('config', 'config'),
            (new FkField('app_id', 'appId', AppDefinition::class))->addFlags(new CascadeDelete(), new Required()),
            new ManyToOneAssociationField('app', 'app_id', AppDefinition::class),
            (new OneToManyAssociationField('ruleConditions', RuleConditionDefinition::class, 'script_id', 'id'))->addFlags(new SetNullOnDelete()),
            (new TranslationsAssociationField(AppScriptConditionTranslationDefinition::class, 'app_script_condition_id'))->addFlags(new Required()),
        ]);
    }
}
