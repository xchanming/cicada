<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\File;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class WindowsStyleFileNameProvider extends FileNameProvider
{
    protected function getNextFileName(string $originalFileName, MediaCollection $relatedMedia, int $iteration): string
    {
        $suffix = $iteration === 0 ? '' : "_($iteration)";

        return $originalFileName . $suffix;
    }
}
