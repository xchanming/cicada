<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaThumbnail;

use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class MediaThumbnailDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'media_thumbnail';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return MediaThumbnailCollection::class;
    }

    public function getEntityClass(): string
    {
        return MediaThumbnailEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return MediaDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware(), new Required()),

            (new IntField('width', 'width'))->addFlags(new ApiAware(), new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new IntField('height', 'height'))->addFlags(new ApiAware(), new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new StringField('url', 'url'))->addFlags(new ApiAware(), new Runtime(['path', 'updatedAt'])),
            (new StringField('path', 'path'))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
