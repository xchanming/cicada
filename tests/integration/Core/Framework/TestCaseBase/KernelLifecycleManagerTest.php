<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\TestCaseBase;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Kernel;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('skip-paratest')]
class KernelLifecycleManagerTest extends TestCase
{
    public function testARebootIsPossible(): void
    {
        $oldKernel = KernelLifecycleManager::getKernel();
        $oldConnection = Kernel::getConnection();
        $oldContainer = $oldKernel->getContainer();

        KernelLifecycleManager::bootKernel(false);

        $newKernel = KernelLifecycleManager::getKernel();
        $newConnection = Kernel::getConnection();

        static::assertNotSame(spl_object_hash($oldKernel), spl_object_hash($newKernel));
        static::assertNotSame(spl_object_hash($oldConnection), spl_object_hash($newConnection));
        static::assertNotSame(spl_object_hash($oldContainer), spl_object_hash($newKernel->getContainer()));
    }

    /*
     * regression test - KernelLifecycleManager::bootKernel used to keep all connections open, due to remaining references.
     * This resulted in case of mariadb in a max connection limit error after 100 connections/calls to bootKernel.
     */
    #[DoesNotPerformAssertions]
    public function testNoConnectionLeak(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            KernelLifecycleManager::bootKernel(true);
        }
    }
}
