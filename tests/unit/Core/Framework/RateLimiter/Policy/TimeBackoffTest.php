<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\RateLimiter\Policy;

use Cicada\Core\Framework\RateLimiter\Policy\TimeBackoff;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TimeBackoff::class)]
class TimeBackoffTest extends TestCase
{
    public function testThrowsExceptionOnInvalidLimits(): void
    {
        $backoff = new TimeBackoff('test', [
            [
                'limit' => 3,
                'interval' => '10 seconds',
            ],
            [
                'limit' => 5,
                'interval' => '30 seconds',
            ],
        ]);

        $stringLimits = ReflectionHelper::getProperty(TimeBackoff::class, 'stringLimits');
        $stringLimits->setValue($backoff, 'invalid');

        static::expectException(\BadMethodCallException::class);
        $backoff->__wakeup();
    }
}
