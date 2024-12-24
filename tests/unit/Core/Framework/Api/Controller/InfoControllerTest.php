<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Flow\Api\FlowActionCollector;
use Cicada\Core\Framework\Api\ApiDefinition\DefinitionService;
use Cicada\Core\Framework\Api\Controller\InfoController;
use Cicada\Core\Framework\Api\Route\ApiRouteInfoResolver;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\BusinessEventCollector;
use Cicada\Core\Framework\Increment\IncrementGatewayRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Store\InAppPurchase;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Kernel;
use Cicada\Core\Maintenance\System\Service\AppUrlVerifier;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(InfoController::class)]
class InfoControllerTest extends TestCase
{
    private InfoController $infoController;

    private ParameterBagInterface&MockObject $parameterBagMock;

    private Kernel&MockObject $kernelMock;

    private RouterInterface&MockObject $routerMock;

    private InAppPurchase $inAppPurchase;

    public function testConfig(): void
    {
        $this->createInstance();

        $this->parameterBagMock->method('get')
            ->willReturnMap([
                ['cicada.html_sanitizer.enabled', true],
                ['cicada.filesystem.private_allowed_extensions', false],
                ['cicada.admin_worker.transports', ['slow']],
                ['cicada.admin_worker.enable_notification_worker', true],
                ['cicada.admin_worker.enable_queue_stats_worker', true],
                ['cicada.admin_worker.enable_admin_worker', true],
                ['kernel.cicada_version', '6.6.9999999-dev'],
                ['kernel.cicada_version_revision', 'PHPUnit'],
                ['cicada.media.enable_url_upload_feature', true],
            ]);

        $this->kernelMock->method('getBundles')
            ->willReturn([
                new AdminExtensionApiPluginWithLocalEntryPoint(true, __DIR__ . '/Fixtures/AdminExtensionApiPluginWithLocalEntryPoint'),
            ]);

        $this->routerMock->method('generate')
            ->with(
                'administration.plugin.index',
                [
                    'pluginName' => 'adminextensionapipluginwithlocalentrypoint',
                ]
            )
            ->willReturn('/admin/adminextensionapipluginwithlocalentrypoint/index.html');

        $response = $this->infoController->config(Context::createDefaultContext(), Request::create('http://localhost'));
        $content = $response->getContent();
        static::assertIsString($content);

        $data = json_decode($content, true);
        static::assertIsArray($data);
        static::assertArrayHasKey('version', $data);
        static::assertSame('6.6.9999999.9999999-dev', $data['version']);
        static::assertArrayHasKey('versionRevision', $data);
        static::assertSame('PHPUnit', $data['versionRevision']);
        static::assertArrayHasKey('adminWorker', $data);

        $workerConfig = $data['adminWorker'];
        static::assertArrayHasKey('enableAdminWorker', $workerConfig);
        static::assertTrue($workerConfig['enableAdminWorker']);
        static::assertArrayHasKey('enableQueueStatsWorker', $workerConfig);
        static::assertTrue($workerConfig['enableQueueStatsWorker']);
        static::assertArrayHasKey('enableNotificationWorker', $workerConfig);
        static::assertTrue($workerConfig['enableNotificationWorker']);
        static::assertArrayHasKey('transports', $workerConfig);
        static::assertIsArray($workerConfig['transports']);
        static::assertCount(1, $workerConfig['transports']);
        static::assertSame('slow', $workerConfig['transports'][0]);

        static::assertArrayHasKey('bundles', $data);
        $bundles = $data['bundles'];
        static::assertIsArray($bundles);
        static::assertCount(1, $bundles);
        static::assertArrayHasKey('AdminExtensionApiPluginWithLocalEntryPoint', $bundles);
        $bundle = $bundles['AdminExtensionApiPluginWithLocalEntryPoint'];
        static::assertIsArray($bundle);
        static::assertArrayHasKey('css', $bundle);
        static::assertIsArray($bundle['css']);
        static::assertCount(0, $bundle['css']);
        static::assertArrayHasKey('js', $bundle);
        static::assertIsArray($bundle['js']);
        static::assertCount(0, $bundle['js']);
        static::assertArrayHasKey('baseUrl', $bundle);
        static::assertSame('/admin/adminextensionapipluginwithlocalentrypoint/index.html', $bundle['baseUrl']);
        static::assertArrayHasKey('type', $bundle);
        static::assertSame('plugin', $bundle['type']);

        static::assertArrayHasKey('settings', $data);
        $settings = $data['settings'];
        static::assertIsArray($settings);
        static::assertArrayHasKey('enableUrlFeature', $settings);
        static::assertTrue($settings['enableUrlFeature']);
        static::assertArrayHasKey('appUrlReachable', $settings);
        static::assertFalse($settings['appUrlReachable']);
        static::assertArrayHasKey('appsRequireAppUrl', $settings);
        static::assertFalse($settings['appsRequireAppUrl']);
        static::assertArrayHasKey('private_allowed_extensions', $settings);
        static::assertFalse($settings['private_allowed_extensions']);
        static::assertArrayHasKey('enableHtmlSanitizer', $settings);
        static::assertTrue($settings['enableHtmlSanitizer']);

        static::assertArrayHasKey('inAppPurchases', $data);
        $inAppPurchases = $data['inAppPurchases'];
        static::assertIsArray($inAppPurchases);
        static::assertCount(1, $inAppPurchases);
        static::assertArrayHasKey('SwagApp', $inAppPurchases);
        static::assertSame(['SwagApp_premium'], $inAppPurchases['SwagApp']);
    }

    private function createInstance(): void
    {
        $this->parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $this->kernelMock = $this->createMock(Kernel::class);
        $this->routerMock = $this->createMock(RouterInterface::class);
        $this->inAppPurchase = StaticInAppPurchaseFactory::createWithFeatures(['SwagApp' => ['SwagApp_premium']]);

        $this->infoController = new InfoController(
            $this->createMock(DefinitionService::class),
            $this->parameterBagMock,
            $this->kernelMock,
            $this->createMock(Packages::class),
            $this->createMock(BusinessEventCollector::class),
            $this->createMock(IncrementGatewayRegistry::class),
            $this->createMock(Connection::class),
            $this->createMock(AppUrlVerifier::class),
            $this->routerMock,
            $this->createMock(FlowActionCollector::class),
            new StaticSystemConfigService(),
            $this->createMock(ApiRouteInfoResolver::class),
            $this->inAppPurchase,
        );
    }
}

/**
 * @internal
 */
class AdminExtensionApiPluginWithLocalEntryPoint extends Plugin
{
    public function getPath(): string
    {
        return \dirname(ReflectionHelper::getFileName(static::class) ?: '') . '/Fixtures/AdminExtensionApiPluginWithLocalEntryPoint';
    }
}
