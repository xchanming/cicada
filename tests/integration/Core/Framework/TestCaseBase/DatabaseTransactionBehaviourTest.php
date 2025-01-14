<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\TestCaseBase;

use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DatabaseTransactionBehaviourTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private bool $setUpIsInTransaction = false;

    protected function setUp(): void
    {
        $this->setUpIsInTransaction = KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class)
            ->isTransactionActive();
    }

    protected function tearDown(): void
    {
        $tearDownIsInTransaction = KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class)
            ->isTransactionActive();

        if (!$tearDownIsInTransaction) {
            throw new \RuntimeException('TearDown does not work correctly');
        }
    }

    public function testInTransaction(): void
    {
        $connection = KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class);

        static::assertTrue($connection->isTransactionActive());
    }

    public function testSetUpIsAlsoInTransaction(): void
    {
        static::assertTrue($this->setUpIsInTransaction);
    }

    public function testLastTestCaseIsSet(): void
    {
        static::assertEquals($this->nameWithDataSet(), static::$lastTestCase);
    }

    public function testTransactionOpenWithoutClose(): void
    {
        static::expectException(ExpectationFailedException::class);
        static::expectExceptionMessage('The previous test case\'s transaction was not closed properly');
        static::expectExceptionMessage('Previous Test case: ' . (new \ReflectionClass($this))->getName() . '::' . static::$lastTestCase);
        static::startTransactionBefore();
    }
}
