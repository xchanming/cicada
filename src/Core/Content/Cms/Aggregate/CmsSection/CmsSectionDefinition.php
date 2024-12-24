<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Aggregate\CmsSection;

use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class CmsSectionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'cms_section';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CmsSectionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CmsSectionCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return CmsPageDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        $collection = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new IntField('position', 'position'))->addFlags(new ApiAware(), new Required()),
            (new StringField('type', 'type'))->addFlags(new ApiAware(), new Required()),
            new LockedField(),
            (new StringField('name', 'name'))->addFlags(new ApiAware()),
            (new StringField('sizing_mode', 'sizingMode'))->addFlags(new ApiAware()),
            (new StringField('mobile_behavior', 'mobileBehavior'))->addFlags(new ApiAware()),
            (new StringField('background_color', 'backgroundColor'))->addFlags(new ApiAware()),
            (new FkField('background_media_id', 'backgroundMediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new StringField('background_media_mode', 'backgroundMediaMode'))->addFlags(new ApiAware()),
            (new StringField('css_class', 'cssClass'))->addFlags(new ApiAware()),
            (new FkField('cms_page_id', 'pageId', CmsPageDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new JsonField('visibility', 'visibility', [
                new BoolField('mobile', 'mobile'),
                new BoolField('desktop', 'desktop'),
                new BoolField('tablet', 'tablet'),
            ]))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('page', 'cms_page_id', CmsPageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('backgroundMedia', 'background_media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('blocks', CmsBlockDefinition::class, 'cms_section_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);

        $collection->add(new VersionField());
        $collection->add((new ReferenceVersionField(CmsPageDefinition::class))->addFlags(new Required(), new ApiAware()));

        return $collection;
    }
}
