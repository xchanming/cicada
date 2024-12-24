<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer\Finish;

use Cicada\Core\Installer\Finish\SystemLocker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SystemLocker::class)]
class SystemLockerTest extends TestCase
{
    public function testLock(): void
    {
        $locker = new SystemLocker(__DIR__);
        $locker->lock();

        static::assertFileExists(__DIR__ . '/install.lock');
        unlink(__DIR__ . '/install.lock');
    }
}
