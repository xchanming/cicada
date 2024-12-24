<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Version;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Version\Cleanup\CleanupVersionTaskHandler;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
class CleanupVersionTaskHandlerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private CleanupVersionTaskHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = static::getContainer()->get(CleanupVersionTaskHandler::class);
    }

    public function testCleanup(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM version');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM version_commit');

        $ids = new IdsCollection();

        $date = new \DateTime();

        $this->createVersion($ids->create('version-1'), $date);

        $date->modify(\sprintf('-%d day', 31));
        $this->createVersion($ids->create('version-2'), $date);

        $this->handler->run();

        $versions = static::getContainer()->get(Connection::class)->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM version');
        static::assertCount(1, $versions);
        static::assertContains($ids->get('version-1'), $versions);

        $commits = static::getContainer()->get(Connection::class)->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM version_commit');
        static::assertCount(1, $commits);
        static::assertContains($ids->get('version-1'), $commits);

        $data = static::getContainer()->get(Connection::class)->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM version_commit_data');
        static::assertCount(1, $data);
        static::assertContains($ids->get('version-1'), $data);
    }

    private function createVersion(string $id, \DateTime $date): void
    {
        $version = [
            'id' => Uuid::fromHexToBytes($id),
            'name' => 'test',
            'created_at' => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        static::getContainer()->get(Connection::class)
            ->insert('version', $version);

        $commit = [
            'id' => Uuid::fromHexToBytes($id),
            'version_id' => Uuid::fromHexToBytes($id),
            'created_at' => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        static::getContainer()->get(Connection::class)
            ->insert('version_commit', $commit);

        $data = [
            'id' => Uuid::fromHexToBytes($id),
            'version_commit_id' => Uuid::fromHexToBytes($id),
            'entity_name' => 'test',
            'entity_id' => json_encode([]),
            'action' => '',
            'payload' => json_encode([]),
            'created_at' => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        static::getContainer()->get(Connection::class)
            ->insert('version_commit_data', $data);
    }
}
