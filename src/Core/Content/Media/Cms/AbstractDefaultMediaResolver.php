<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Cms;

use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
abstract class AbstractDefaultMediaResolver
{
    abstract public function getDecorated(): AbstractDefaultMediaResolver;

    abstract public function getDefaultCmsMediaEntity(string $mediaAssetFilePath): ?MediaEntity;
}
