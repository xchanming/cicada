<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Extension;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Storefront\Theme\Aggregate\ThemeTranslationDefinition;

#[Package('framework')]
class LanguageExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField('themeTranslations', ThemeTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return LanguageDefinition::class;
    }

    public function getEntityName(): string
    {
        return LanguageDefinition::ENTITY_NAME;
    }
}
