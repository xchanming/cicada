<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Increment;

use Cicada\Core\Framework\Increment\ArrayIncrementer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArrayIncrementer::class)]
class ArrayIncrementerTest extends TestCase
{
    private ArrayIncrementer $arrayIncrementer;

    protected function setUp(): void
    {
        $this->arrayIncrementer = new ArrayIncrementer();
        $this->arrayIncrementer->setPool('user-activity-pool');
    }

    public function testDecrementDoesNotCreate(): void
    {
        $this->arrayIncrementer->decrement('test', 'test');
        static::assertEmpty($this->arrayIncrementer->list('test'));
    }

    public function testIncrement(): void
    {
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertNotNull($list['sw.product.index']);
        static::assertEquals(1, $list['sw.product.index']['count']);

        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(2, $list['sw.product.index']['count']);
    }

    public function testDecrement(): void
    {
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertNotNull($list['sw.product.index']);
        static::assertEquals(2, $list['sw.product.index']['count']);

        $this->arrayIncrementer->decrement('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(1, $list['sw.product.index']['count']);
    }

    public function testList(): void
    {
        $this->arrayIncrementer->increment('test-user-1', 'sw.order.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(2, array_values($list)[0]['count']);
        static::assertEquals('sw.product.index', array_values($list)[0]['key']);
        static::assertEquals(1, array_values($list)[1]['count']);

        // List will return in DESC order of record's count
        $this->arrayIncrementer->increment('test-user-1', 'sw.order.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.order.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(3, array_values($list)[0]['count']);
        static::assertEquals('sw.order.index', array_values($list)[0]['key']);
        static::assertEquals(2, array_values($list)[1]['count']);

        static::assertEmpty($this->arrayIncrementer->list('test2'));
    }

    public function testReset(): void
    {
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertNotEmpty($list);

        $this->arrayIncrementer->reset('test-user-1');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(0, $list['sw.product.index']['count']);

        $this->arrayIncrementer->increment('test-user-1', 'sw.order.index');
        $this->arrayIncrementer->increment('test-user-1', 'sw.product.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(1, $list['sw.product.index']['count']);
        static::assertEquals(1, $list['sw.order.index']['count']);

        $this->arrayIncrementer->reset('test-user-1', 'sw.order.index');

        $list = $this->arrayIncrementer->list('test-user-1');

        static::assertEquals(1, $list['sw.product.index']['count']);
        static::assertEquals(0, $list['sw.order.index']['count']);
    }
}
