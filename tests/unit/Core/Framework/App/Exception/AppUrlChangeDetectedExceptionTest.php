<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Exception;

use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppUrlChangeDetectedException::class)]
class AppUrlChangeDetectedExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new AppUrlChangeDetectedException('oldUrl', 'currentUrl', 'shopId');

        static::assertSame(
            'Detected APP_URL change, was "oldUrl" and is now "currentUrl".',
            $exception->getMessage()
        );

        static::assertSame(
            'oldUrl',
            $exception->getPreviousUrl()
        );

        static::assertSame(
            'currentUrl',
            $exception->getCurrentUrl()
        );

        static::assertSame(
            'shopId',
            $exception->getShopId()
        );
    }
}
