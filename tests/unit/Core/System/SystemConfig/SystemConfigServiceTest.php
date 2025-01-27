<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SystemConfig;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Webhook\Hookable;
use Cicada\Core\System\SystemConfig\AbstractSystemConfigLoader;
use Cicada\Core\System\SystemConfig\Event\BeforeSystemConfigMultipleChangedEvent;
use Cicada\Core\System\SystemConfig\Event\SystemConfigMultipleChangedEvent;
use Cicada\Core\System\SystemConfig\SymfonySystemConfigService;
use Cicada\Core\System\SystemConfig\SystemConfigException;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\SystemConfig\Util\ConfigReader;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[CoversClass(SystemConfigService::class)]
class SystemConfigServiceTest extends TestCase
{
    private Connection&MockObject $connection;

    private ConfigReader&MockObject $configReader;

    private AbstractSystemConfigLoader&MockObject $configLoader;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private SystemConfigService $configService;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->configReader = $this->createMock(ConfigReader::class);
        $this->configLoader = $this->createMock(AbstractSystemConfigLoader::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->configService = new SystemConfigService(
            $this->connection,
            $this->configReader,
            $this->configLoader,
            $this->eventDispatcher,
            new SymfonySystemConfigService([]),
            true,
        );
    }

    public function testMultipleChangedEventsFired(): void
    {
        $beforeEventAssert = function (Event|Hookable $event): void {
            static::assertInstanceOf(BeforeSystemConfigMultipleChangedEvent::class, $event);
            $event->setValue('foo.bar', 40);
        };

        $eventAssert = function (Event|Hookable $event): void {
            static::assertInstanceOf(SystemConfigMultipleChangedEvent::class, $event);
            static::assertSame(40, $event->getConfig()['foo.bar']);
        };

        $expects = static::exactly(7);
        $this->eventDispatcher
            ->expects($expects)
            ->method('dispatch')
            ->willReturnCallback(function (Event|Hookable $event) use ($expects, $beforeEventAssert, $eventAssert) {
                match ($expects->numberOfInvocations()) {
                    1 => $beforeEventAssert($event),
                    7 => $eventAssert($event),
                    default => null,
                };

                return $event;
            });

        $this->configService->setMultiple(['foo.bar' => 'value', 'bar.foo' => 50], TestDefaults::SALES_CHANNEL);
    }

    /**
     * @param array<string> $tags
     */
    #[DataProvider('provideTracingExamples')]
    public function testTracing(bool $enabled, array $tags): void
    {
        Feature::skipTestIfActive('cache_rework', $this);

        $config = new SystemConfigService(
            $this->connection,
            $this->configReader,
            $this->configLoader,
            $this->eventDispatcher,
            new SymfonySystemConfigService([]),
            $enabled
        );

        $config->trace('test', function () use ($config): void {
            $config->get('test');
        });

        static::assertSame($tags, $config->getTrace('test'));
    }

    public function testNotAllowedToSetKeysManagedBySystem(): void
    {
        $configService = new SystemConfigService(
            $this->connection,
            $this->configReader,
            $this->configLoader,
            $this->eventDispatcher,
            new SymfonySystemConfigService(['default' => ['core.test' => true]]),
            true,
        );

        // Setting the same value is okay
        $configService->set('core.test', true);

        static::expectExceptionObject(SystemConfigException::systemConfigKeyIsManagedBySystems('core.test'));

        $configService->set('core.test', false);
    }

    public static function provideTracingExamples(): \Generator
    {
        yield 'disabled' => [
            false,
            [
                'global.system.config',
            ],
        ];

        yield 'enabled' => [
            true,
            [
                'config.test',
            ],
        ];
    }
}
