<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\NumberRange\ValueGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementRedisStorage;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

/**
 * @internal
 */
#[CoversClass(IncrementRedisStorage::class)]
class IncrementRedisStorageTest extends TestCase
{
    private MockObject&LockFactory $lockFactoryMock;

    private MockObject&\Redis $redisMock;

    private IncrementRedisStorage $storage;

    protected function setUp(): void
    {
        $this->lockFactoryMock = $this->createMock(LockFactory::class);
        $this->redisMock = $this->createMock('Redis');

        $this->storage = new IncrementRedisStorage(
            $this->redisMock,
            $this->lockFactoryMock,
            new StaticEntityRepository([])
        );
    }

    public function testReserveReturnsIncrementIfStartOfPatternIsLowerThenTheIncrement(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 5,
            'pattern' => 'n',
        ];

        $this->lockFactoryMock->expects(static::never())
            ->method('createLock');

        $this->redisMock->expects(static::once())
            ->method('incr')
            ->with($this->getKey($config['id']))
            ->willReturn(10);

        static::assertEquals(10, $this->storage->reserve($config));
    }

    public function testReserveWithoutStart(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => null,
            'pattern' => 'n',
        ];

        $this->lockFactoryMock->expects(static::never())
            ->method('createLock');

        $this->redisMock->expects(static::once())
            ->method('incr')
            ->with($this->getKey($config['id']))
            ->willReturn(10);

        static::assertEquals(10, $this->storage->reserve($config));
    }

    public function testReserveDoesNotLockIfIncrementValueEqualsStart(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 5,
            'pattern' => 'n',
        ];

        $this->lockFactoryMock->expects(static::never())
            ->method('createLock');

        $this->redisMock->expects(static::once())
            ->method('incr')
            ->with($this->getKey($config['id']))
            ->willReturn(5);

        static::assertEquals(5, $this->storage->reserve($config));
    }

    public function testReserveDoesSetStartValueIfItCanAcquireLock(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 10,
            'pattern' => 'n',
        ];

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(static::once())
            ->method('acquire')
            ->willReturn(true);

        $lock->expects(static::once())
            ->method('release');

        $this->lockFactoryMock->expects(static::once())
            ->method('createLock')
            ->willReturn($lock);

        $this->redisMock->expects(static::once())
            ->method('incr')
            ->with($this->getKey($config['id']))
            ->willReturn(5);

        $this->redisMock->expects(static::once())
            ->method('incrBy')
            ->with($this->getKey($config['id']), 5)
            ->willReturn(10);

        static::assertEquals(10, $this->storage->reserve($config));
    }

    public function testReserveDoesNotSetStartValueIfItCanNotAcquireLock(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 10,
            'pattern' => 'n',
        ];

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(static::once())
            ->method('acquire')
            ->willReturn(false);

        $lock->expects(static::never())
            ->method('release');

        $this->lockFactoryMock->expects(static::once())
            ->method('createLock')
            ->willReturn($lock);

        $this->redisMock->expects(static::once())
            ->method('incr')
            ->with($this->getKey($config['id']))
            ->willReturn(5);

        $this->redisMock->expects(static::never())
            ->method('incrBy');

        static::assertEquals(5, $this->storage->reserve($config));
    }

    public function testPreviewIfValueIsNotSetAndNoStart(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => null,
            'pattern' => 'n',
        ];

        $this->redisMock->expects(static::once())
            ->method('get')
            ->with($this->getKey($config['id']))
            ->willReturn(null);

        static::assertEquals(1, $this->storage->preview($config));
    }

    public function testPreviewWillReturnStartValueIfNoValueIsSet(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 10,
            'pattern' => 'n',
        ];

        $this->redisMock->expects(static::once())
            ->method('get')
            ->with($this->getKey($config['id']))
            ->willReturn(null);

        static::assertEquals(10, $this->storage->preview($config));
    }

    public function testPreviewWillReturnStartValueIfIncrementValueIsLower(): void
    {
        $config = [
            'id' => Uuid::randomHex(),
            'start' => 10,
            'pattern' => 'n',
        ];

        $this->redisMock->expects(static::once())
            ->method('get')
            ->with($this->getKey($config['id']))
            ->willReturn(8);

        static::assertEquals(10, $this->storage->preview($config));
    }

    public function testList(): void
    {
        $idSearchResult = new IdSearchResult(
            2,
            [['data' => '10', 'primaryKey' => 'abc'], ['data' => '5', 'primaryKey' => 'def']],
            new Criteria(),
            Context::createDefaultContext()
        );

        $numberRangeIds = ['abc' => '10', 'def' => '5'];

        $keys = array_map(fn (string $id) => [$this->getKey($id)], $numberRangeIds);
        $this->redisMock->expects(static::exactly(\count($keys)))
            ->method('get')
            ->willReturnOnConsecutiveCalls('10', '5', false);

        $this->storage = new IncrementRedisStorage(
            $this->redisMock,
            $this->lockFactoryMock,
            new StaticEntityRepository([$idSearchResult])
        );

        static::assertSame(['abc' => 10, 'def' => 5], $this->storage->list());
    }

    public function testSet(): void
    {
        $configId = Uuid::randomHex();

        $this->redisMock->expects(static::once())
            ->method('set')
            ->with($this->getKey($configId), 10);

        $this->storage->set($configId, 10);
    }

    private function getKey(string $id): string
    {
        return 'number_range:' . $id;
    }
}
