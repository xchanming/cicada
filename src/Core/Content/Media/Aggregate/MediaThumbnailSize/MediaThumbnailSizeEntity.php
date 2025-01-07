<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize;

use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class MediaThumbnailSizeEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var int<1, max>
     */
    protected $width;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var int<1, max>
     */
    protected $height;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var MediaFolderConfigurationCollection|null
     */
    protected $mediaFolderConfigurations;

    /**
     * @return int<1, max>
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int<1, max> $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * @return int<1, max>
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int<1, max> $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    public function getMediaFolderConfigurations(): ?MediaFolderConfigurationCollection
    {
        return $this->mediaFolderConfigurations;
    }

    public function setMediaFolderConfigurations(MediaFolderConfigurationCollection $mediaFolderConfigurations): void
    {
        $this->mediaFolderConfigurations = $mediaFolderConfigurations;
    }
}
