<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
interface TypeDetectorInterface
{
    public function detect(MediaFile $mediaFile, ?MediaType $previouslyDetectedType): ?MediaType;
}
