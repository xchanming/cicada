<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\DocumentGenerator;

use Cicada\Core\Checkout\Document\DocumentGenerator\Counter;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Counter::class)]
class CounterTest extends TestCase
{
    public function testCounter(): void
    {
        $counter = new Counter();

        static::assertSame(0, $counter->getCounter());

        $counter->increment();

        static::assertSame(1, $counter->getCounter());

        $counter->increment();
        $counter->increment();
        $counter->increment();

        static::assertSame(4, $counter->getCounter());
    }
}
