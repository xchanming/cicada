<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Aggregate\PluginTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginDefinition;

#[Package('core')]
class PluginTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'plugin_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PluginTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return PluginTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return PluginDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('label', 'label'))->addFlags(new Required()),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml()),
            new StringField('manufacturer_link', 'manufacturerLink'),
            new StringField('support_link', 'supportLink'),
            new CustomFields(),
        ]);
    }
}
