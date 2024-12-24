<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\ScheduledTask;

use Cicada\Core\Framework\MessageQueue\Command\RegisterScheduledTasksCommand;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class RegisterScheduledTaskTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testNoValidationErrors(): void
    {
        $taskRegistry = $this->createMock(TaskRegistry::class);
        $taskRegistry->expects(static::once())
            ->method('registerTasks');

        $commandTester = new CommandTester(new RegisterScheduledTasksCommand($taskRegistry));
        $commandTester->execute([]);
    }
}
