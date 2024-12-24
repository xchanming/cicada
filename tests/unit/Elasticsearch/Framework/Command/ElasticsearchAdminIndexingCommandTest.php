<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Command;

use Cicada\Elasticsearch\Admin\AdminIndexingBehavior;
use Cicada\Elasticsearch\Admin\AdminSearchRegistry;
use Cicada\Elasticsearch\Framework\Command\ElasticsearchAdminIndexingCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(ElasticsearchAdminIndexingCommand::class)]
class ElasticsearchAdminIndexingCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $registry = $this->getMockBuilder(AdminSearchRegistry::class)->disableOriginalConstructor()->getMock();

        $registry->expects(static::any())->method('iterate')->with(new AdminIndexingBehavior(true, [], ['promotion']));
        $commandTester = new CommandTester(new ElasticsearchAdminIndexingCommand($registry));
        $commandTester->execute(['--no-queue' => true, '--only' => 'promotion']);

        $commandTester->assertCommandIsSuccessful();
    }
}
