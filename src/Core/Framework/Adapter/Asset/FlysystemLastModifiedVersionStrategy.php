<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Asset;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

#[Package('framework')]
class FlysystemLastModifiedVersionStrategy implements VersionStrategyInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $cacheTag,
        private readonly FilesystemOperator $filesystem,
        private readonly TagAwareAdapterInterface $cacheAdapter
    ) {
    }

    public function getVersion(string $path): string
    {
        return $this->applyVersion($path);
    }

    public function applyVersion(string $path): string
    {
        $lastModified = $this->getLastModified($path);

        return $path . $lastModified;
    }

    private function getLastModified(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $cacheKey = 'metaDataFlysystem-' . Hasher::hash($path);

        $item = $this->cacheAdapter->getItem($cacheKey);

        if ($item->isHit()) {
            return (string) $item->get();
        }

        $metaData = '';
        if ($this->filesystem->fileExists($path)) {
            $metaData = '?' . $this->filesystem->lastModified($path);
        }

        $item->set($metaData);
        $item->tag($this->cacheTag);
        $this->cacheAdapter->saveDeferred($item);

        return (string) $item->get();
    }
}
