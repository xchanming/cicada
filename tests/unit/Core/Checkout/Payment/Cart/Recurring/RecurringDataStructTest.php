<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\Recurring;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(RecurringDataStruct::class)]
class RecurringDataStructTest extends TestCase
{
    public function testGetters(): void
    {
        $time = new \DateTime();
        $struct = new RecurringDataStruct('foo', $time);

        static::assertSame('foo', $struct->getSubscriptionId());
        static::assertSame($time, $struct->getNextSchedule());
    }
}
