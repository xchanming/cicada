<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class CustomFieldTestTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'attribute_test_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return CustomFieldTestDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([new CustomFields('custom_translated', 'customTranslated')]);
    }
}
