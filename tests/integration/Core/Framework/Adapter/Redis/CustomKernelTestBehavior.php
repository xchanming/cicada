<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Redis;

use Cicada\Core\Framework\Adapter\Kernel\KernelFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Kernel;

/**
 * @internal
 *
 * @template KernelClass of Kernel
 */
#[Package('framework')]
trait CustomKernelTestBehavior
{
    /**
     * @var KernelClass
     */
    private static Kernel $kernel;

    public static function loadKernel(): void
    {
        $oldKernelClass = KernelFactory::$kernelClass;
        KernelFactory::$kernelClass = self::getKernelClass();
        /** @var KernelClass $kernel */
        $kernel = KernelLifecycleManager::createKernel(self::getKernelClass());
        KernelFactory::$kernelClass = $oldKernelClass; // Do not forget to recover default kernel class!

        $kernel->boot();
        self::$kernel = $kernel;
    }

    public static function unloadKernel(): void
    {
        self::$kernel->shutdown();
    }

    /**
     * @return class-string<KernelClass>
     */
    abstract private static function getKernelClass(): string;
}
