<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MediaFolderConfigurationEntity>
 */
#[Package('discovery')]
class MediaFolderConfigurationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'media_folder_configuration_collection';
    }

    protected function getExpectedClass(): string
    {
        return MediaFolderConfigurationEntity::class;
    }
}
