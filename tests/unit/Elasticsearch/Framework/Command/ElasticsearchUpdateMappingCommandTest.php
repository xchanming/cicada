<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Command;

use Cicada\Elasticsearch\Framework\Command\ElasticsearchUpdateMappingCommand;
use Cicada\Elasticsearch\Framework\Indexing\IndexMappingUpdater;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(ElasticsearchUpdateMappingCommand::class)]
class ElasticsearchUpdateMappingCommandTest extends TestCase
{
    public function testUpdate(): void
    {
        $updater = $this->createMock(IndexMappingUpdater::class);
        $updater
            ->expects(static::once())
            ->method('update');

        $command = new ElasticsearchUpdateMappingCommand(
            $updater
        );

        $tester = new CommandTester($command);
        $tester->execute([]);
    }
}
