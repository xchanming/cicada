<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Strategy;

use Cicada\Core\Content\Media\Core\Application\AbstractMediaPathStrategy;
use Cicada\Core\Content\Media\Core\Params\MediaLocationStruct;
use Cicada\Core\Content\Media\Core\Params\ThumbnailLocationStruct;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal Concrete implementation is not allowed to be decorated or extended. The implementation details can change
 */
#[Package('buyers-experience')]
class PhysicalFilenamePathStrategy extends AbstractMediaPathStrategy
{
    public function name(): string
    {
        return 'physical_filename';
    }

    protected function value(MediaLocationStruct|ThumbnailLocationStruct $location): ?string
    {
        $media = $location instanceof ThumbnailLocationStruct ? $location->media : $location;

        $timestamp = $media->uploadedAt ? $media->uploadedAt->getTimestamp() . '/' : '';

        return $timestamp . $media->fileName;
    }
}
