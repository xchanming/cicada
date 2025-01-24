<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\IndexerQueuer;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Migration\V6_6\Migration1710493619ScheduleMediaPathIndexer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(Migration1710493619ScheduleMediaPathIndexer::class)]
class Migration1710493619ScheduleMediaPathIndexerTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testMigrate(): void
    {
        $queuer = static::getContainer()->get(IndexerQueuer::class);
        $queuer->finishIndexer(['media.path.post_update']);
        $queuedIndexers = $queuer->getIndexers();

        static::assertArrayNotHasKey('media.path.post_update', $queuedIndexers);

        $m = new Migration1710493619ScheduleMediaPathIndexer();
        $m->update($this->connection);
        $m->update($this->connection);

        $queuedIndexers = $queuer->getIndexers();
        static::assertArrayHasKey('media.path.post_update', $queuedIndexers);
    }
}
