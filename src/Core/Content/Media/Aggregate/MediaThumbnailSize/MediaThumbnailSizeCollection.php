<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MediaThumbnailSizeEntity>
 */
#[Package('buyers-experience')]
class MediaThumbnailSizeCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'media_thumbnail_size_collection';
    }

    protected function getExpectedClass(): string
    {
        return MediaThumbnailSizeEntity::class;
    }
}
