<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme\Subscriber;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Plugin\Context\ActivateContext;
use Cicada\Core\Framework\Plugin\Context\UpdateContext;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreUpdateEvent;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Core\Framework\Test\Plugin\PluginTestsHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber;
use Cicada\Storefront\Theme\ThemeLifecycleHandler;
use Cicada\Storefront\Theme\ThemeLifecycleService;
use PHPUnit\Framework\TestCase;
use SwagTestPlugin\SwagTestPlugin;

/**
 * @internal
 */
class PluginLifecycleSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use PluginTestsHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addTestPluginToKernel(
            __DIR__ . '/../../../../../src/Core/Framework/Test/Plugin/_fixture/plugins/SwagTestPlugin',
            'SwagTestPlugin'
        );
    }

    public function testDoesNotAddPluginStorefrontConfigurationToConfigurationCollectionIfItIsAddedAlready(): void
    {
        $context = Context::createDefaultContext();
        $event = new PluginPostActivateEvent(
            $this->getPlugin(),
            new ActivateContext(
                $this->createMock(Plugin::class),
                $context,
                '6.1.0',
                '1.0.0',
                $this->createMock(MigrationCollection::class)
            )
        );
        $storefrontPluginConfigMock = new StorefrontPluginConfiguration('SwagTest');
        // Plugin storefront config is already added here
        $storefrontPluginConfigCollection = new StorefrontPluginConfigurationCollection([$storefrontPluginConfigMock]);

        $pluginConfigurationFactory = $this->createMock(AbstractStorefrontPluginConfigurationFactory::class);
        $pluginConfigurationFactory->method('createFromBundle')->willReturn($storefrontPluginConfigMock);
        $storefrontPluginRegistry = $this->createMock(StorefrontPluginRegistry::class);
        $storefrontPluginRegistry->method('getConfigurations')->willReturn($storefrontPluginConfigCollection);
        $handler = $this->createMock(ThemeLifecycleHandler::class);
        $handler->expects(static::once())->method('handleThemeInstallOrUpdate')->with(
            $storefrontPluginConfigMock,
            // This ensures the plugin storefront config is not added twice
            static::equalTo($storefrontPluginConfigCollection),
            $context,
        );

        $subscriber = new PluginLifecycleSubscriber(
            $storefrontPluginRegistry,
            __DIR__,
            $pluginConfigurationFactory,
            $handler,
            $this->createMock(ThemeLifecycleService::class)
        );

        $subscriber->pluginPostActivate($event);
    }

    public function testAddsThePluginStorefrontConfigurationToConfigurationCollectionIfItWasNotAddedAlready(): void
    {
        $context = Context::createDefaultContext();
        $event = new PluginPostActivateEvent(
            $this->getPlugin(),
            new ActivateContext(
                $this->createMock(Plugin::class),
                $context,
                '6.1.0',
                '1.0.0',
                $this->createMock(MigrationCollection::class)
            )
        );
        $storefrontPluginConfigMock = new StorefrontPluginConfiguration('SwagTest');
        // Plugin storefront config is not added here
        $storefrontPluginConfigCollection = new StorefrontPluginConfigurationCollection([]);

        $pluginConfigurationFactory = $this->createMock(AbstractStorefrontPluginConfigurationFactory::class);
        $pluginConfigurationFactory->method('createFromBundle')->willReturn($storefrontPluginConfigMock);
        $storefrontPluginRegistry = $this->createMock(StorefrontPluginRegistry::class);
        $storefrontPluginRegistry->method('getConfigurations')->willReturn($storefrontPluginConfigCollection);
        $collectionWithPluginConfig = clone $storefrontPluginConfigCollection;
        $collectionWithPluginConfig->add($storefrontPluginConfigMock);
        $handler = $this->createMock(ThemeLifecycleHandler::class);
        $handler->expects(static::once())->method('handleThemeInstallOrUpdate')->with(
            $storefrontPluginConfigMock,
            // This ensures the plugin storefront config was added in the subscriber
            static::equalTo($collectionWithPluginConfig),
            $context,
        );

        $subscriber = new PluginLifecycleSubscriber(
            $storefrontPluginRegistry,
            __DIR__,
            $pluginConfigurationFactory,
            $handler,
            $this->createMock(ThemeLifecycleService::class)
        );

        $subscriber->pluginPostActivate($event);
    }

    public function testThemeLifecycleIsNotCalledWhenDeactivatedUsingContextOnActivate(): void
    {
        $context = Context::createDefaultContext();
        $context->addState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING);
        $event = new PluginPostActivateEvent(
            $this->getPlugin(),
            new ActivateContext(
                $this->createMock(Plugin::class),
                $context,
                '6.1.0',
                '1.0.0',
                $this->createMock(MigrationCollection::class)
            )
        );

        $handler = $this->createMock(ThemeLifecycleHandler::class);
        $handler->expects(static::never())->method('handleThemeInstallOrUpdate');

        $subscriber = new PluginLifecycleSubscriber(
            $this->createMock(StorefrontPluginRegistry::class),
            __DIR__,
            $this->createMock(AbstractStorefrontPluginConfigurationFactory::class),
            $handler,
            $this->createMock(ThemeLifecycleService::class)
        );

        $subscriber->pluginPostActivate($event);
    }

    public function testThemeLifecycleIsNotCalledWhenDeactivatedUsingContextOnUpdate(): void
    {
        $context = Context::createDefaultContext();
        $context->addState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING);
        $event = new PluginPreUpdateEvent(
            $this->getPlugin(),
            new UpdateContext(
                $this->createMock(Plugin::class),
                $context,
                '6.1.0',
                '1.0.0',
                $this->createMock(MigrationCollection::class),
                '1.0.1'
            )
        );

        $handler = $this->createMock(ThemeLifecycleHandler::class);
        $handler->expects(static::never())->method('handleThemeInstallOrUpdate');

        $subscriber = new PluginLifecycleSubscriber(
            $this->createMock(StorefrontPluginRegistry::class),
            __DIR__,
            $this->createMock(AbstractStorefrontPluginConfigurationFactory::class),
            $handler,
            $this->createMock(ThemeLifecycleService::class)
        );

        $subscriber->pluginUpdate($event);
    }

    private function getPlugin(): PluginEntity
    {
        return (new PluginEntity())
            ->assign([
                'path' => (new \ReflectionClass(SwagTestPlugin::class))->getFileName(),
                'baseClass' => SwagTestPlugin::class,
            ]);
    }
}
