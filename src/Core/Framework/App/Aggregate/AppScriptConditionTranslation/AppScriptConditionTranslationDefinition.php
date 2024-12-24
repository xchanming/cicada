<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\AppScriptConditionTranslation;

use Cicada\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AppScriptConditionTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'app_script_condition_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AppScriptConditionTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return AppScriptConditionTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.4.10.3';
    }

    protected function getParentDefinitionClass(): string
    {
        return AppScriptConditionDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
        ]);
    }
}
