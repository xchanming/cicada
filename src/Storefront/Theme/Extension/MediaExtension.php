<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Extension;

use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\Aggregate\ThemeMediaDefinition;
use Cicada\Storefront\Theme\ThemeDefinition;

#[Package('storefront')]
class MediaExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('themes', ThemeDefinition::class, 'preview_media_id')
        );

        $collection->add(
            new ManyToManyAssociationField('themeMedia', ThemeDefinition::class, ThemeMediaDefinition::class, 'media_id', 'theme_id')
        );
    }

    public function getDefinitionClass(): string
    {
        return MediaDefinition::class;
    }

    public function getEntityName(): string
    {
        return MediaDefinition::ENTITY_NAME;
    }
}
