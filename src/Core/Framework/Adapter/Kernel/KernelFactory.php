<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Kernel;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Adapter\Cache\CacheIdLoader;
use Cicada\Core\Framework\Adapter\Database\MySQLFactory;
use Cicada\Core\Framework\Adapter\Storage\MySQLKeyValueStorage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\DbalKernelPluginLoader;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Cicada\Core\Kernel;
use Cicada\Core\Profiling\Doctrine\ProfilingMiddleware;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Cicada\Core\Framework\Adapter\Kernel\KernelFactory
 *      Cicada\Core\Kernel
 *          Cicada\Core\Framework\Adapter\Kernel\HttpCacheKernel (http caching)
 *              Cicada\Core\Framework\Adapter\Kernel\HttpKernel (runs request transformer)
 *                  Cicada\Storefront\Controller\Any
 *
 * @final
 */
#[Package('core')]
class KernelFactory
{
    /**
     * @var class-string<Kernel>
     */
    public static string $kernelClass = Kernel::class;

    public static function create(
        string $environment,
        bool $debug,
        ClassLoader $classLoader,
        ?KernelPluginLoader $pluginLoader = null,
        ?Connection $connection = null
    ): HttpKernelInterface {
        if (InstalledVersions::isInstalled('cicada/platform')) {
            $cicadaVersion = InstalledVersions::getVersion('cicada/platform')
                . '@' . InstalledVersions::getReference('cicada/platform');
        } else {
            $cicadaVersion = InstalledVersions::getVersion('cicada/core')
                . '@' . InstalledVersions::getReference('cicada/core');
        }

        $middlewares = [];
        if ((\PHP_SAPI !== 'cli' || \in_array('--profile', $_SERVER['argv'] ?? [], true))
            && $environment !== 'prod' && InstalledVersions::isInstalled('symfony/doctrine-bridge')) {
            $middlewares = [new ProfilingMiddleware()];
        }

        $connection = $connection ?? MySQLFactory::create($middlewares);

        $pluginLoader = $pluginLoader ?? new DbalKernelPluginLoader($classLoader, null, $connection);

        $storage = new MySQLKeyValueStorage($connection);
        $cacheId = (new CacheIdLoader($storage))->load();

        /** @var KernelInterface $kernel */
        $kernel = new static::$kernelClass(
            $environment,
            $debug,
            $pluginLoader,
            $cacheId,
            $cicadaVersion,
            $connection,
            self::getProjectDir()
        );

        return $kernel;
    }

    private static function getProjectDir(): string
    {
        if ($dir = $_ENV['PROJECT_ROOT'] ?? $_SERVER['PROJECT_ROOT'] ?? false) {
            return $dir;
        }

        $r = new \ReflectionClass(self::class);

        /** @var string $dir */
        $dir = $r->getFileName();

        $dir = $rootDir = \dirname($dir);
        while (!file_exists($dir . '/vendor')) {
            if ($dir === \dirname($dir)) {
                return $rootDir;
            }
            $dir = \dirname($dir);
        }

        return $dir;
    }
}
