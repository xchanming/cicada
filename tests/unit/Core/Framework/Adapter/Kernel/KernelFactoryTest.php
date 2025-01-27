<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Kernel;

use Cicada\Core\Framework\Adapter\Kernel\KernelFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;
use Cicada\Core\Profiling\Doctrine\ProfilingMiddleware;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Doctrine\DBAL\Driver\Middleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(KernelFactory::class)]
class KernelFactoryTest extends TestCase
{
    public function testProfilingMiddlewareIsAddedWhenFlagPresent(): void
    {
        if (!InstalledVersions::isInstalled('symfony/doctrine-bridge')) {
            static::markTestSkipped('profiler not installed');
        }

        $_SERVER['argv'][] = '--profile';

        $kernel = KernelFactory::create(
            'dev',
            true,
            new ClassLoader(),
        );
        static::assertInstanceOf(Kernel::class, $kernel);

        $middlewares = array_map(
            fn (Middleware $middleware) => $middleware::class,
            $kernel::getConnection()->getConfiguration()->getMiddlewares()
        );

        static::assertContains(ProfilingMiddleware::class, $middlewares);
    }
}
