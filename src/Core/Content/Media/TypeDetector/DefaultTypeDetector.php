<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\AudioType;
use Cicada\Core\Content\Media\MediaType\BinaryType;
use Cicada\Core\Content\Media\MediaType\ImageType;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Content\Media\MediaType\VideoType;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class DefaultTypeDetector implements TypeDetectorInterface
{
    public function detect(MediaFile $mediaFile, ?MediaType $previouslyDetectedType): ?MediaType
    {
        if ($previouslyDetectedType !== null) {
            return $previouslyDetectedType;
        }

        $mime = explode('/', $mediaFile->getMimeType());

        return match ($mime[0]) {
            'image' => new ImageType(),
            'video' => new VideoType(),
            'audio' => new AudioType(),
            default => new BinaryType(),
        };
    }
}
