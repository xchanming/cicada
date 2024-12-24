<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Cache;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\AbstractReverseProxyGateway;
use Cicada\Core\Framework\Adapter\Kernel\KernelFactory;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Kernel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Group('skip-paratest')]
#[Group('slow')]
class CacheClearerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testCleanupOldKernelDirectories(): void
    {
        $classLoader = clone KernelLifecycleManager::getClassLoader();
        KernelLifecycleManager::getClassLoader()->unregister();
        $classLoader->register();

        $original = KernelLifecycleManager::getKernel();

        $oldCacheDirs = [];
        for ($i = 0; $i < 2; ++$i) {
            $class = KernelLifecycleManager::getKernelClass();
            /** @var Kernel $kernel */
            $kernel = new $class(
                'test',
                true,
                new StaticKernelPluginLoader($classLoader),
                Uuid::randomHex(),
                '1.0.0@' . $i . '1eec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33',
                $original->getContainer()->get(Connection::class),
                EnvironmentHelper::getVariable('PROJECT_ROOT')
            );

            $kernel->boot();
            $oldCacheDir = $kernel->getCacheDir();
            static::assertFileExists($oldCacheDir);
            $kernel->shutdown();
            $oldCacheDirs[] = $oldCacheDir;
        }
        $oldCacheDirs = array_unique($oldCacheDirs);

        static::assertCount(2, $oldCacheDirs);

        $second = KernelLifecycleManager::getKernel();
        $second->boot();
        static::assertFileExists($second->getCacheDir());

        static::assertNotContains($second->getCacheDir(), $oldCacheDirs);

        static::getContainer()->get(CacheClearer::class)->clear();

        foreach ($oldCacheDirs as $oldCacheDir) {
            static::assertFileDoesNotExist($oldCacheDir);
        }
    }

    public function testClearContainerCache(): void
    {
        $previousKernelClass = KernelFactory::$kernelClass;

        // We need a new cache dir, therefore we reuse the StaticAnalyzeKernel class
        KernelFactory::$kernelClass = StaticAnalyzeKernel::class;

        /** @var Kernel $newTestKernel */
        $newTestKernel = KernelFactory::create(
            'test',
            true,
            KernelLifecycleManager::getClassLoader(),
            new StaticKernelPluginLoader(KernelLifecycleManager::getClassLoader()),
            static::getContainer()->get(Connection::class)
        );

        // reset kernel class for further tests
        KernelFactory::$kernelClass = $previousKernelClass;

        $newTestKernel->boot();
        $cacheDir = $newTestKernel->getCacheDir();
        $newTestKernel->shutdown();

        $finder = (new Finder())->in($cacheDir)->directories()->name('Container*');
        $containerCaches = [];

        foreach ($finder->getIterator() as $containerPaths) {
            $containerCaches[] = $containerPaths->getRealPath();
        }

        static::assertCount(1, $containerCaches);

        $filesystem = static::getContainer()->get('filesystem');
        $cacheClearer = new CacheClearer(
            [],
            static::getContainer()->get('cache_clearer'),
            null,
            static::getContainer()->get(CacheInvalidator::class),
            $filesystem,
            $cacheDir,
            'test',
            false,
            static::getContainer()->get('messenger.bus.cicada'),
            static::getContainer()->get('logger')
        );

        $cacheClearer->clearContainerCache();

        foreach ($containerCaches as $containerCache) {
            static::assertFileDoesNotExist($containerCache);
        }

        $filesystem->remove($cacheDir);
    }

    public function testUrlGeneratorCacheGetsCleared(): void
    {
        $cacheClearer = static::getContainer()->get(CacheClearer::class);

        touch(\sprintf('%s%sUrlGenerator.php', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));
        touch(\sprintf('%s%sUrlGenerator.php.meta', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));

        $urlGeneratorCacheFileFinder = (new Finder())->in($this->getKernel()->getCacheDir())->files()->name('UrlGenerator.php*');

        static::assertCount(2, $urlGeneratorCacheFileFinder);

        $cacheClearer->clear();

        foreach ($urlGeneratorCacheFileFinder->getIterator() as $generatorFile) {
            static::assertFileDoesNotExist($generatorFile->getRealPath());
        }
    }

    public function testUrlGeneratorCacheGetsNotClearedInClusterMode(): void
    {
        $cacheClearer = new CacheClearer(
            [],
            static::getContainer()->get('cache_clearer'),
            null,
            static::getContainer()->get(CacheInvalidator::class),
            static::getContainer()->get('filesystem'),
            $this->getKernel()->getCacheDir(),
            'test',
            true,
            static::getContainer()->get('messenger.bus.cicada'),
            static::getContainer()->get('logger')
        );

        touch(\sprintf('%s%sUrlGenerator.php', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));
        touch(\sprintf('%s%sUrlGenerator.php.meta', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));

        $urlGeneratorCacheFileFinder = (new Finder())->in($this->getKernel()->getCacheDir())->files()->name('UrlGenerator.php*');

        static::assertCount(2, $urlGeneratorCacheFileFinder);

        $cacheClearer->clear();

        foreach ($urlGeneratorCacheFileFinder->getIterator() as $generatorFile) {
            static::assertFileExists($generatorFile->getRealPath());
        }
    }

    public function testClearHttpCache(): void
    {
        $reverseProxyCache = $this->createMock(AbstractReverseProxyGateway::class);
        $reverseProxyCache->expects(static::once())->method('banAll');

        $cacheClearer = new CacheClearer(
            [],
            $this->createMock(CacheClearerInterface::class),
            $reverseProxyCache,
            $this->createMock(CacheInvalidator::class),
            new Filesystem(),
            $this->getKernel()->getCacheDir(),
            'test',
            true,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        $cacheClearer->clearHttpCache();
    }

    public function testClearHttpCacheWithoutReverseProxy(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects(static::once())->method('clear');

        $cacheClearer = new CacheClearer(
            ['http' => $pool],
            $this->createMock(CacheClearerInterface::class),
            null,
            $this->createMock(CacheInvalidator::class),
            new Filesystem(),
            $this->getKernel()->getCacheDir(),
            'test',
            true,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        $cacheClearer->clearHttpCache();
    }
}
