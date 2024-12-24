<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test;

use Cicada\Core\Framework\Log\Package;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * @internal
 */
#[Package('core')]
class TestCacheClearer
{
    protected CacheClearerInterface $cacheClearer;

    protected string $cacheDir;

    protected Filesystem $filesystem;

    /**
     * @var CacheItemPoolInterface[]
     */
    protected array $adapters;

    /**
     * @param CacheItemPoolInterface[] $adapters
     */
    public function __construct(
        array $adapters,
        CacheClearerInterface $cacheClearer,
        string $cacheDir
    ) {
        $this->adapters = $adapters;
        $this->cacheClearer = $cacheClearer;
        $this->cacheDir = $cacheDir;
    }

    public function clear(): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->clear();
        }

        $this->cacheClearer->clear($this->cacheDir);
    }
}
