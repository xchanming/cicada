<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\System\Command;

use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Core\Framework\Update\Api\UpdateController;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Cicada\Core\Framework\Update\Event\UpdatePreFinishEvent;
use Cicada\Core\Maintenance\System\Command\SystemUpdateFinishCommand;
use Cicada\Core\Test\Stub\EventDispatcher\CollectingEventDispatcher;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(SystemUpdateFinishCommand::class)]
class SystemUpdateFinishCommandTest extends TestCase
{
    private CollectingEventDispatcher $eventDispatcher;

    private StaticSystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->eventDispatcher = new CollectingEventDispatcher();
        $this->systemConfigService = new StaticSystemConfigService();
        $this->systemConfigService->set(UpdateController::UPDATE_PREVIOUS_VERSION_KEY, '6.4.0.0');
    }

    public function testRunCommand(): void
    {
        $command = new SystemUpdateFinishCommand($this->eventDispatcher, $this->systemConfigService, '6.5.0.0');

        $application = $this->createMock(Application::class);
        $application
            ->expects(static::exactly(3))
            ->method('find')
            ->willReturn($this->createMock(Command::class));

        $application->method('doRun')->willReturn(Command::SUCCESS);

        $command->setApplication($application);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $events = $this->eventDispatcher->getEvents();

        static::assertCount(2, $events);

        $event = $events[0];

        static::assertInstanceOf(UpdatePreFinishEvent::class, $event);

        static::assertSame('6.5.0.0', $event->getNewVersion());
        static::assertSame('6.4.0.0', $event->getOldVersion());

        $finishEvent = $events[1];

        static::assertInstanceOf(UpdatePostFinishEvent::class, $finishEvent);

        static::assertFalse($event->getContext()->hasState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING));
    }

    public function testRunCommandSkipAssetBuild(): void
    {
        $command = new SystemUpdateFinishCommand($this->eventDispatcher, $this->systemConfigService, '6.5.0.0');

        $application = $this->createMock(Application::class);
        $migrationCommand = $this->createMock(Command::class);
        $migrationCommand->method('run')->willReturn(Command::SUCCESS);

        $application
            ->expects(static::exactly(2))
            ->method('find')
            ->willReturn($migrationCommand);

        $application->method('doRun')->willReturn(Command::SUCCESS);

        $command->setApplication($application);
        $tester = new CommandTester($command);

        $tester->execute(['--skip-asset-build' => true]);
        $tester->assertCommandIsSuccessful();

        $events = $this->eventDispatcher->getEvents();

        static::assertCount(2, $events);

        $event = $events[0];

        static::assertInstanceOf(UpdatePreFinishEvent::class, $event);

        static::assertTrue($event->getContext()->hasState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING));
    }

    public function testSkipAll(): void
    {
        $command = new SystemUpdateFinishCommand($this->eventDispatcher, $this->systemConfigService, '6.5.0.0');
        $application = $this->createMock(Application::class);
        $application
            ->expects(static::never())
            ->method('find');

        $command->setApplication($application);

        $tester = new CommandTester($command);

        $tester->execute(['--skip-migrations' => true, '--skip-asset-build' => true]);
        $tester->assertCommandIsSuccessful();
    }
}
