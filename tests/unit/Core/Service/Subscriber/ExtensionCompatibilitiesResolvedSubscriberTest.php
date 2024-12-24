<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Cicada\Core\Framework\Update\Event\ExtensionCompatibilitiesResolvedEvent;
use Cicada\Core\Framework\Update\Services\ExtensionCompatibility;
use Cicada\Core\Framework\Update\Struct\Version;
use Cicada\Core\Service\ServiceRegistryClient;
use Cicada\Core\Service\ServiceRegistryEntry;
use Cicada\Core\Service\Subscriber\ExtensionCompatibilitiesResolvedSubscriber;

/**
 * @internal
 */
#[CoversClass(ExtensionCompatibilitiesResolvedSubscriber::class)]
class ExtensionCompatibilitiesResolvedSubscriberTest extends TestCase
{
    private ServiceRegistryClient&MockObject $serviceRegistryClient;

    private Version $update;

    protected function setUp(): void
    {
        $this->update = new Version([
            'version' => '6.7.0.0',
            'title' => 'Cicada 6.7.0.0',
            'body' => 'Cicada 6.7.0.0',
            'date' => new \DateTimeImmutable(),
        ]);

        $this->serviceRegistryClient = $this->createMock(ServiceRegistryClient::class);
    }

    public function testAppWithSameNameAsServiceIsMarkedAsUpdatableFuture(): void
    {
        $compatibilities = [
            [
                'name' => 'TestApp',
                'managedByComposer' => false,
                'installedVersion' => '1.0.0',
                'statusVariant' => 'error',
                'statusColor' => null,
                'statusMessage' => '',
                'statusName' => ExtensionCompatibility::PLUGIN_COMPATIBILITY_NOT_IN_STORE,
            ],
        ];

        $this->serviceRegistryClient->expects(static::once())
            ->method('getAll')
            ->willReturn([
                new ServiceRegistryEntry('TestApp', 'TestApp', 'https://www.testapp.com', '/'),
            ]);

        $subscriber = new ExtensionCompatibilitiesResolvedSubscriber(
            $this->serviceRegistryClient,
        );

        $event = new ExtensionCompatibilitiesResolvedEvent(
            $this->update,
            new ExtensionCollection([]),
            $compatibilities,
            Context::createDefaultContext(),
        );

        $subscriber->markAppsWithServiceAsCompatible($event);

        static::assertCount(1, $event->compatibilities);
        static::assertSame('updatableFuture', $event->compatibilities[0]['statusName']);
        static::assertSame('With new Cicada version', $event->compatibilities[0]['statusMessage']);
        static::assertSame('yellow', $event->compatibilities[0]['statusColor']);
        static::assertNull($event->compatibilities[0]['statusVariant']);
    }

    public function testIncompatibleAppWithNoServiceIsMarkedAsNotInStore(): void
    {
        $compatibilities = [
            [
                'name' => 'TestApp',
                'managedByComposer' => false,
                'installedVersion' => '1.0.0',
                'statusVariant' => 'error',
                'statusColor' => null,
                'statusMessage' => '',
                'statusName' => ExtensionCompatibility::PLUGIN_COMPATIBILITY_NOT_IN_STORE,
            ],
        ];

        $this->serviceRegistryClient->expects(static::once())
            ->method('getAll')
            ->willReturn([]);

        $subscriber = new ExtensionCompatibilitiesResolvedSubscriber(
            $this->serviceRegistryClient,
        );

        $event = new ExtensionCompatibilitiesResolvedEvent(
            $this->update,
            new ExtensionCollection([]),
            $compatibilities,
            Context::createDefaultContext(),
        );

        $subscriber->markAppsWithServiceAsCompatible($event);

        static::assertCount(1, $event->compatibilities);
        static::assertSame('notInStore', $event->compatibilities[0]['statusName']);
        static::assertSame('', $event->compatibilities[0]['statusMessage']);
        static::assertNull($event->compatibilities[0]['statusColor']);
        static::assertSame('error', $event->compatibilities[0]['statusVariant']);
    }
}
