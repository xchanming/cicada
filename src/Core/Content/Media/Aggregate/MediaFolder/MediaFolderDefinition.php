<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaFolder;

use Cicada\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreePathField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class MediaFolderDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'media_folder';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return MediaFolderCollection::class;
    }

    public function getEntityClass(): string
    {
        return MediaFolderEntity::class;
    }

    public function getDefaults(): array
    {
        return ['useParentConfiguration' => true];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new BoolField('use_parent_configuration', 'useParentConfiguration'),
            (new FkField('media_folder_configuration_id', 'configurationId', MediaFolderConfigurationDefinition::class))->addFlags(new Required()),
            new FkField('default_folder_id', 'defaultFolderId', MediaDefaultFolderDefinition::class),
            new ParentFkField(self::class),
            new ParentAssociationField(self::class, 'id'),
            new ChildrenAssociationField(self::class),
            new ChildCountField(),
            new TreePathField('path', 'path'),
            (new OneToManyAssociationField('media', MediaDefinition::class, 'media_folder_id'))->addFlags(new SetNullOnDelete()),
            new OneToOneAssociationField('defaultFolder', 'default_folder_id', 'id', MediaDefaultFolderDefinition::class, false),
            new ManyToOneAssociationField('configuration', 'media_folder_configuration_id', MediaFolderConfigurationDefinition::class, 'id', false),
            (new StringField('name', 'name'))->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING), new Required()),
            new CustomFields(),
        ]);
    }
}
