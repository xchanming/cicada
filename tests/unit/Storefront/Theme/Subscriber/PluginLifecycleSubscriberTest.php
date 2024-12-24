<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Plugin\Context\ActivateContext;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivationFailedEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreUpdateEvent;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber;
use Cicada\Storefront\Theme\ThemeLifecycleHandler;
use Cicada\Storefront\Theme\ThemeLifecycleService;

/**
 * @internal
 */
#[CoversClass(PluginLifecycleSubscriber::class)]
class PluginLifecycleSubscriberTest extends TestCase
{
    private PluginLifecycleSubscriber $pluginSubscriber;

    protected function setUp(): void
    {
        $this->pluginSubscriber = new PluginLifecycleSubscriber(
            $this->createMock(StorefrontPluginRegistry::class),
            '',
            $this->createMock(StorefrontPluginConfigurationFactory::class),
            $this->createMock(ThemeLifecycleHandler::class),
            $this->createMock(ThemeLifecycleService::class),
        );
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals(
            [
                PluginPostActivateEvent::class => 'pluginPostActivate',
                PluginPreUpdateEvent::class => 'pluginUpdate',
                PluginPreDeactivateEvent::class => 'pluginDeactivateAndUninstall',
                PluginPostDeactivateEvent::class => 'pluginPostDeactivate',
                PluginPostDeactivationFailedEvent::class => 'pluginPostDeactivateFailed',
                PluginPreUninstallEvent::class => 'pluginDeactivateAndUninstall',
                PluginPostUninstallEvent::class => 'pluginPostUninstall',
            ],
            PluginLifecycleSubscriber::getSubscribedEvents()
        );
    }

    public function testSkipPostCompile(): void
    {
        $context = Context::createDefaultContext();
        $context->addState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING);
        $activateContextMock = $this->createMock(ActivateContext::class);
        $activateContextMock->expects(static::once())->method('getContext')->willReturn($context);
        $eventMock = $this->createMock(PluginPostActivateEvent::class);
        $eventMock->expects(static::once())->method('getContext')->willReturn($activateContextMock);
        $eventMock->expects(static::never())->method('getPlugin');

        $this->pluginSubscriber->pluginPostActivate($eventMock);
    }

    public function testPluginPostActivate(): void
    {
        $pluginMock = new PluginEntity();
        $pluginMock->setPath('');
        $pluginMock->setBaseClass(FakePlugin::class);
        $eventMock = $this->createMock(PluginPostActivateEvent::class);
        $eventMock->expects(static::exactly(2))->method('getPlugin')->willReturn($pluginMock);
        $this->pluginSubscriber->pluginPostActivate($eventMock);
    }

    public function testPluginPostDeactivateFailed(): void
    {
        $pluginMock = new PluginEntity();
        $pluginMock->setPath('');
        $pluginMock->setBaseClass(FakePlugin::class);

        $eventMock = $this->createMock(PluginPostDeactivationFailedEvent::class);
        $eventMock->expects(static::exactly(2))->method('getPlugin')->willReturn($pluginMock);
        $this->pluginSubscriber->pluginPostDeactivateFailed($eventMock);
    }
}

/**
 * @internal
 */
class FakePlugin extends Plugin
{
}
