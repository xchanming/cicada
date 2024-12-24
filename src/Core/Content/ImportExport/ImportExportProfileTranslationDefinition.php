<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class ImportExportProfileTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = ImportExportProfileDefinition::ENTITY_NAME . '_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ImportExportProfileTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ImportExportProfileTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.2.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return ImportExportProfileDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new StringField('label', 'label'),
        ]);
    }
}
