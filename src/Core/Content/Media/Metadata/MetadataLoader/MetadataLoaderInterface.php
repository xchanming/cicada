<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Metadata\MetadataLoader;

use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface MetadataLoaderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function extractMetadata(string $filePath): ?array;

    public function supports(MediaType $mediaType): bool;
}
