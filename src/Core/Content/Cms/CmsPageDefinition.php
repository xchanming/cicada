<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('discovery')]
class CmsPageDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'cms_page';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CmsPageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CmsPageCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new StringField('type', 'type'))->addFlags(new ApiAware(), new Required()),
            (new StringField('entity', 'entity'))->addFlags(new ApiAware()),
            (new StringField('css_class', 'cssClass'))->addFlags(new ApiAware()),
            (new JsonField('config', 'config', [
                (new StringField('background_color', 'backgroundColor'))->addFlags(new ApiAware()),
            ]))->addFlags(new ApiAware()),
            (new FkField('preview_media_id', 'previewMediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            new LockedField(),

            (new OneToManyAssociationField('sections', CmsSectionDefinition::class, 'cms_page_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new TranslationsAssociationField(CmsPageTranslationDefinition::class, 'cms_page_id'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('previewMedia', 'preview_media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),

            (new OneToManyAssociationField('categories', CategoryDefinition::class, 'cms_page_id'))->addFlags(new RestrictDelete()),
            (new OneToManyAssociationField('landingPages', LandingPageDefinition::class, 'cms_page_id'))->addFlags(new ApiAware(), new RestrictDelete()),
            (new OneToManyAssociationField('homeSalesChannels', SalesChannelDefinition::class, 'home_cms_page_id'))->addFlags(new RestrictDelete()),
            (new OneToManyAssociationField('products', ProductDefinition::class, 'cms_page_id'))->addFlags(new RestrictDelete()),
        ]);
    }
}
