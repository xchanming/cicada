<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\AppTranslation;

use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Since;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'app_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return AppTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AppTranslationCollection::class;
    }

    public function since(): ?string
    {
        return '6.3.1.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return AppDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('label', 'label'))->addFlags(new Required()),
            new LongTextField('description', 'description'),
            new LongTextField('privacy_policy_extensions', 'privacyPolicyExtensions'),
            (new CustomFields())->addFlags(new Since('6.4.1.0')),
        ]);
    }
}
