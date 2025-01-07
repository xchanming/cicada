<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Metadata;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Content\Media\Metadata\MetadataLoader\MetadataLoaderInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class MetadataLoader
{
    /**
     * @internal
     *
     * @param MetadataLoaderInterface[] $metadataLoader
     */
    public function __construct(private readonly iterable $metadataLoader)
    {
    }

    public function loadFromFile(MediaFile $mediaFile, MediaType $mediaType): ?array
    {
        foreach ($this->metadataLoader as $loader) {
            if ($loader->supports($mediaType)) {
                $metaData = $loader->extractMetadata($mediaFile->getFileName());

                if ($mediaFile->getHash()) {
                    $metaData['hash'] = $mediaFile->getHash();
                }

                return $metaData;
            }
        }

        return null;
    }
}
