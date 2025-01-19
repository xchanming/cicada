<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Migration\V6_3\Migration1536233560BasicData;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
class BasicDataUntouchedTest extends TestCase
{
    public function testBasicDataUntouched(): void
    {
        $file = KernelLifecycleManager::getClassLoader()->findFile(Migration1536233560BasicData::class);
        static::assertIsString($file);

        static::assertSame(
            '5f31e964eaa1c8acebb650971672d4c3',
            Hasher::hashFile($file),
            'BasicData migration has changed. This is not allowed.'
        );
    }
}
