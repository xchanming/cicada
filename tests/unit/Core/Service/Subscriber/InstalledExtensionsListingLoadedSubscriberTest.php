<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Store\Event\InstalledExtensionsListingLoadedEvent;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Service\Subscriber\InstalledExtensionsListingLoadedSubscriber;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

/**
 * @internal
 */
#[CoversClass(InstalledExtensionsListingLoadedSubscriber::class)]
class InstalledExtensionsListingLoadedSubscriberTest extends TestCase
{
    public function testExtensionsWithSameNameAsServicesAreRemoved(): void
    {
        $event = new InstalledExtensionsListingLoadedEvent(
            new ExtensionCollection([
                'Ext1' => ExtensionStruct::fromArray(['name' => 'Ext1', 'label' => 'Ext1', 'type' => 'type']),
                'Ext2' => ExtensionStruct::fromArray(['name' => 'Ext2', 'label' => 'Ext2', 'type' => 'type']),
            ]),
            Context::createDefaultContext()
        );

        $app1 = new AppEntity();
        $app1->setUniqueIdentifier(Uuid::randomHex());
        $app1->setName('Ext2');

        /** @var StaticEntityRepository<AppCollection> $appRepository */
        $appRepository = new StaticEntityRepository([
            new AppCollection([$app1]),
        ]);
        $subscriber = new InstalledExtensionsListingLoadedSubscriber($appRepository);

        $subscriber->removeAppsWithService($event);

        static::assertCount(1, $event->extensionCollection);
        $ext = $event->extensionCollection->first();
        static::assertNotNull($ext);
        static::assertEquals('Ext1', $ext->getName());
    }
}
