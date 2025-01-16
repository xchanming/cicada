<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow\Indexing;

use Cicada\Core\Content\Flow\Indexing\FlowIndexerSubscriber;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
class FlowIndexerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use QueueTestBehaviour;

    public function testIndexingHappensAfterPluginLifecycle(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        $connection->executeStatement('UPDATE `flow` SET `payload` = null, `invalid` = 0');

        $indexer = static::getContainer()->get(FlowIndexerSubscriber::class);
        $indexer->refreshPlugin();

        $this->runWorker();

        static::assertGreaterThan(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM flow WHERE payload IS NOT NULL'));
    }
}
