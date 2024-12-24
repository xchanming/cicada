<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Controller;

use Cicada\Administration\Controller\AdministrationController;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Content\Flow\Api\FlowActionCollector;
use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Defaults;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Api\ApiDefinition\DefinitionService;
use Cicada\Core\Framework\Api\Controller\InfoController;
use Cicada\Core\Framework\Api\Route\ApiRouteInfoResolver;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\BusinessEventCollector;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\CustomerGroupAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Store\InAppPurchase;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Kernel;
use Cicada\Core\Maintenance\System\Service\AppUrlVerifier;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\Stub\Framework\BundleFixture;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class InfoControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    use AppSystemTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testGetConfig(): void
    {
        $expected = [
            'version' => '6.6.9999999.9999999-dev',
            'versionRevision' => str_repeat('0', 32),
            'adminWorker' => [
                'enableAdminWorker' => true,
                'enableQueueStatsWorker' => true,
                'enableNotificationWorker' => true,
                'transports' => ['async', 'low_priority'],
            ],
            'bundles' => [],
            'settings' => [
                'enableUrlFeature' => true,
                'appUrlReachable' => true,
                'appsRequireAppUrl' => false,
                'private_allowed_extensions' => [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                    'avif',
                    'gif',
                    'svg',
                    'bmp',
                    'tiff',
                    'tif',
                    'eps',
                    'webm',
                    'mkv',
                    'flv',
                    'ogv',
                    'ogg',
                    'mov',
                    'mp4',
                    'avi',
                    'wmv',
                    'pdf',
                    'aac',
                    'mp3',
                    'wav',
                    'flac',
                    'oga',
                    'wma',
                    'txt',
                    'doc',
                    'ico',
                    'glb',
                    'zip',
                    'rar',
                    'csv',
                    'xls',
                    'xlsx',
                ],
                'enableHtmlSanitizer' => true,
                'enableStagingMode' => false,
                'disableExtensionManagement' => false,
            ],
            'inAppPurchases' => [],
        ];

        $url = '/api/_info/config';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $decodedResponse = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        // reset environment based miss match
        $decodedResponse['bundles'] = [];
        $decodedResponse['versionRevision'] = $expected['versionRevision'];

        static::assertEquals($expected, $decodedResponse);
    }

    public function testGetConfigWithPermissions(): void
    {
        $ids = new IdsCollection();
        $appRepository = static::getContainer()->get('app.repository');
        $appRepository->create([
            [
                'name' => 'PHPUnit',
                'path' => '/foo/bar',
                'active' => true,
                'configurable' => false,
                'version' => '1.0.0',
                'label' => 'PHPUnit',
                'integration' => [
                    'id' => $ids->create('integration'),
                    'label' => 'foo',
                    'accessKey' => '123',
                    'secretAccessKey' => '456',
                ],
                'aclRole' => [
                    'name' => 'PHPUnitRole',
                    'privileges' => [
                        'user:create',
                        'user:read',
                        'user:update',
                        'user:delete',
                        'user_change_me',
                    ],
                ],
                'baseAppUrl' => 'https://example.com',
            ],
        ], Context::createDefaultContext());

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $bundle = [
            'active' => true,
            'integrationId' => $ids->get('integration'),
            'type' => 'app',
            'baseUrl' => 'https://example.com',
            'permissions' => [
                'create' => ['user'],
                'read' => ['user'],
                'update' => ['user'],
                'delete' => ['user'],
                'additional' => ['user_change_me'],
            ],
            'version' => '1.0.0',
            'name' => 'PHPUnit',
        ];

        $expected = [
            'version' => Kernel::CICADA_FALLBACK_VERSION,
            'versionRevision' => str_repeat('0', 32),
            'adminWorker' => [
                'enableAdminWorker' => true,
                'transports' => [],
            ],
            'bundles' => $bundle,
            'settings' => [
                'enableUrlFeature' => true,
                'enableHtmlSanitizer' => true,
            ],
        ];

        $url = '/api/_info/config';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $decodedResponse = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        foreach (array_keys($expected) as $key) {
            static::assertArrayHasKey($key, $decodedResponse);
        }

        $bundles = $decodedResponse['bundles'];
        static::assertIsArray($bundles);
        static::assertArrayHasKey('PHPUnit', $bundles);
        static::assertIsArray($bundles['PHPUnit']);
        static::assertSame($bundle, $bundles['PHPUnit']);
    }

    public function testGetCicadaVersion(): void
    {
        $expected = [
            'version' => '6.6.9999999.9999999-dev',
        ];

        $url = '/api/_info/version';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        static::assertSame(200, $client->getResponse()->getStatusCode());

        $version = mb_substr(json_encode($expected, \JSON_THROW_ON_ERROR), 0, -3);
        static::assertNotEmpty($version);
        static::assertStringStartsWith($version, $content);
    }

    public function testGetCicadaVersionOldVersion(): void
    {
        $expected = [
            'version' => '6.6.9999999.9999999-dev',
        ];

        $url = '/api/v1/_info/version';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        static::assertSame(200, $client->getResponse()->getStatusCode());

        $version = mb_substr(json_encode($expected, \JSON_THROW_ON_ERROR), 0, -3);
        static::assertNotEmpty($version);
        static::assertStringStartsWith($version, $content);
    }

    public function testBusinessEventRoute(): void
    {
        $url = '/api/_info/events.json';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        $expected = [
            [
                'name' => 'checkout.customer.login',
                'class' => CustomerLoginEvent::class,
                'extensions' => [],
                'data' => [
                    'customer' => [
                        'type' => 'entity',
                        'entityClass' => CustomerDefinition::class,
                        'entityName' => 'customer',
                    ],
                    'contextToken' => [
                        'type' => 'string',
                    ],
                ],
                'aware' => [
                    ScalarValuesAware::class,
                    lcfirst((new \ReflectionClass(ScalarValuesAware::class))->getShortName()),
                    SalesChannelAware::class,
                    lcfirst((new \ReflectionClass(SalesChannelAware::class))->getShortName()),
                    MailAware::class,
                    lcfirst((new \ReflectionClass(MailAware::class))->getShortName()),
                    CustomerAware::class,
                    lcfirst((new \ReflectionClass(CustomerAware::class))->getShortName()),
                ],
            ],
            [
                'name' => 'checkout.order.placed',
                'class' => CheckoutOrderPlacedEvent::class,
                'extensions' => [],
                'data' => [
                    'order' => [
                        'type' => 'entity',
                        'entityClass' => OrderDefinition::class,
                        'entityName' => 'order',
                    ],
                ],
                'aware' => [
                    CustomerAware::class,
                    lcfirst((new \ReflectionClass(CustomerAware::class))->getShortName()),
                    CustomerGroupAware::class,
                    lcfirst((new \ReflectionClass(CustomerGroupAware::class))->getShortName()),
                    MailAware::class,
                    lcfirst((new \ReflectionClass(MailAware::class))->getShortName()),
                    SalesChannelAware::class,
                    lcfirst((new \ReflectionClass(SalesChannelAware::class))->getShortName()),
                    OrderAware::class,
                    lcfirst((new \ReflectionClass(OrderAware::class))->getShortName()),
                ],
            ],
            [
                'name' => 'state_enter.order_delivery.state.shipped_partially',
                'class' => OrderStateMachineStateChangeEvent::class,
                'extensions' => [],
                'data' => [
                    'order' => [
                        'type' => 'entity',
                        'entityClass' => OrderDefinition::class,
                        'entityName' => 'order',
                    ],
                ],
                'aware' => [
                    MailAware::class,
                    lcfirst((new \ReflectionClass(MailAware::class))->getShortName()),
                    SalesChannelAware::class,
                    lcfirst((new \ReflectionClass(SalesChannelAware::class))->getShortName()),
                    OrderAware::class,
                    lcfirst((new \ReflectionClass(OrderAware::class))->getShortName()),
                    CustomerAware::class,
                    lcfirst((new \ReflectionClass(CustomerAware::class))->getShortName()),
                ],
            ],
        ];

        foreach ($expected as $event) {
            $actualEvents = array_values(array_filter($response, fn ($x) => $x['name'] === $event['name']));
            sort($event['aware']);
            sort($actualEvents[0]['aware']);
            static::assertNotEmpty($actualEvents, 'Event with name "' . $event['name'] . '" not found');
            static::assertCount(1, $actualEvents);
            static::assertEquals($event, $actualEvents[0], $event['name']);
        }
    }

    public function testBundlePaths(): void
    {
        $kernelMock = $this->createMock(Kernel::class);
        $packagesMock = $this->createMock(Packages::class);
        $eventCollector = $this->createMock(FlowActionCollector::class);
        $infoController = new InfoController(
            $this->createMock(DefinitionService::class),
            new ParameterBag([
                'kernel.cicada_version' => 'cicada-version',
                'kernel.cicada_version_revision' => 'cicada-version-revision',
                'cicada.admin_worker.enable_admin_worker' => 'enable-admin-worker',
                'cicada.admin_worker.enable_queue_stats_worker' => 'enable-queue-stats-worker',
                'cicada.admin_worker.enable_notification_worker' => 'enable-notification-worker',
                'cicada.admin_worker.transports' => 'transports',
                'cicada.filesystem.private_allowed_extensions' => ['png'],
                'cicada.html_sanitizer.enabled' => true,
                'cicada.media.enable_url_upload_feature' => true,
                'cicada.staging.administration.show_banner' => true,
                'cicada.deployment.runtime_extension_management' => true,
            ]),
            $kernelMock,
            $packagesMock,
            $this->createMock(BusinessEventCollector::class),
            static::getContainer()->get('cicada.increment.gateway.registry'),
            $this->connection,
            static::getContainer()->get(AppUrlVerifier::class),
            static::getContainer()->get('router'),
            $eventCollector,
            static::getContainer()->get(SystemConfigService::class),
            static::getContainer()->get(ApiRouteInfoResolver::class),
            static::getContainer()->get(InAppPurchase::class),
        );

        $infoController->setContainer($this->createMock(Container::class));

        $assetPackage = $this->createMock(Package::class);
        $packagesMock
            ->expects(static::exactly(1))
            ->method('getPackage')
            ->willReturn($assetPackage);
        $assetPackage
            ->expects(static::exactly(1))
            ->method('getUrl')
            ->willReturnArgument(0);

        $kernelMock
            ->expects(static::exactly(1))
            ->method('getBundles')
            ->willReturn([new BundleFixture('SomeFunctionalityBundle', __DIR__ . '/Fixtures/InfoController')]);

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $content = $infoController->config(Context::createDefaultContext(), Request::create($appUrl))->getContent();
        static::assertNotFalse($content);
        $config = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('SomeFunctionalityBundle', $config['bundles']);

        $jsFilePath = explode('?', (string) $config['bundles']['SomeFunctionalityBundle']['js'][0])[0];
        static::assertEquals(
            'bundles/somefunctionality/administration/js/some-functionality-bundle.js',
            $jsFilePath
        );
    }

    public function testBundlePathsWithMarkerOnly(): void
    {
        $kernelMock = $this->createMock(Kernel::class);
        $packagesMock = $this->createMock(Packages::class);
        $eventCollector = $this->createMock(FlowActionCollector::class);
        $infoController = new InfoController(
            $this->createMock(DefinitionService::class),
            new ParameterBag([
                'kernel.cicada_version' => 'cicada-version',
                'kernel.cicada_version_revision' => 'cicada-version-revision',
                'cicada.admin_worker.enable_admin_worker' => 'enable-admin-worker',
                'cicada.admin_worker.enable_queue_stats_worker' => 'enable-queue-stats-worker',
                'cicada.admin_worker.enable_notification_worker' => 'enable-notification-worker',
                'cicada.admin_worker.transports' => 'transports',
                'cicada.filesystem.private_allowed_extensions' => ['png'],
                'cicada.html_sanitizer.enabled' => true,
                'cicada.media.enable_url_upload_feature' => true,
                'cicada.staging.administration.show_banner' => false,
                'cicada.deployment.runtime_extension_management' => true,
            ]),
            $kernelMock,
            $packagesMock,
            $this->createMock(BusinessEventCollector::class),
            static::getContainer()->get('cicada.increment.gateway.registry'),
            $this->connection,
            static::getContainer()->get(AppUrlVerifier::class),
            static::getContainer()->get('router'),
            $eventCollector,
            static::getContainer()->get(SystemConfigService::class),
            static::getContainer()->get(ApiRouteInfoResolver::class),
            static::getContainer()->get(InAppPurchase::class),
        );

        $infoController->setContainer($this->createMock(Container::class));

        $assetPackage = $this->createMock(Package::class);
        $packagesMock
            ->expects(static::exactly(1))
            ->method('getPackage')
            ->willReturn($assetPackage);
        $assetPackage
            ->expects(static::exactly(1))
            ->method('getUrl')
            ->willReturnArgument(0);

        $kernelMock
            ->expects(static::exactly(1))
            ->method('getBundles')
            ->willReturn([new BundleFixture('SomeFunctionalityBundle', __DIR__ . '/Fixtures/InfoControllerWithMarker')]);

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $content = $infoController->config(Context::createDefaultContext(), Request::create($appUrl))->getContent();
        static::assertNotFalse($content);
        $config = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('SomeFunctionalityBundle', $config['bundles']);

        $jsFilePath = explode('?', (string) $config['bundles']['SomeFunctionalityBundle']['js'][0])[0];
        static::assertEquals(
            'bundles/somefunctionality/administration/js/some-functionality-bundle.js',
            $jsFilePath
        );
    }

    public function testBaseAdminPaths(): void
    {
        if (!class_exists(AdministrationController::class)) {
            static::markTestSkipped('Cannot test without Administration as results will differ');
        }

        $this->clearRequestStack();

        $this->loadAppsFromDir(__DIR__ . '/Fixtures/AdminExtensionApiApp');

        $kernelMock = $this->createMock(Kernel::class);
        $eventCollector = $this->createMock(FlowActionCollector::class);

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $basePath = new UrlPackage([$appUrl], new EmptyVersionStrategy());
        $assets = new Packages($basePath, ['asset' => $basePath]);

        $infoController = new InfoController(
            $this->createMock(DefinitionService::class),
            new ParameterBag([
                'kernel.cicada_version' => 'cicada-version',
                'kernel.cicada_version_revision' => 'cicada-version-revision',
                'cicada.admin_worker.enable_admin_worker' => 'enable-admin-worker',
                'cicada.admin_worker.enable_queue_stats_worker' => 'enable-queue-stats-worker',
                'cicada.admin_worker.enable_notification_worker' => 'enable-notification-worker',
                'cicada.admin_worker.transports' => 'transports',
                'cicada.filesystem.private_allowed_extensions' => ['png'],
                'cicada.html_sanitizer.enabled' => true,
                'cicada.media.enable_url_upload_feature' => true,
                'cicada.staging.administration.show_banner' => false,
                'cicada.deployment.runtime_extension_management' => true,
            ]),
            $kernelMock,
            $assets,
            $this->createMock(BusinessEventCollector::class),
            static::getContainer()->get('cicada.increment.gateway.registry'),
            $this->connection,
            static::getContainer()->get(AppUrlVerifier::class),
            static::getContainer()->get('router'),
            $eventCollector,
            static::getContainer()->get(SystemConfigService::class),
            static::getContainer()->get(ApiRouteInfoResolver::class),
            static::getContainer()->get(InAppPurchase::class),
        );

        $infoController->setContainer($this->createMock(Container::class));

        $kernelMock
            ->expects(static::exactly(1))
            ->method('getBundles')
            ->willReturn([
                new AdminExtensionApiPlugin(true, __DIR__ . '/Fixtures/InfoController'),
                new AdminExtensionApiPluginWithLocalEntryPoint(true, __DIR__ . '/Fixtures/AdminExtensionApiPluginWithLocalEntryPoint'),
            ]);

        $content = $infoController->config(Context::createDefaultContext(), Request::create($appUrl))->getContent();
        static::assertNotFalse($content);
        $config = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertCount(3, $config['bundles']);

        static::assertArrayHasKey('AdminExtensionApiPlugin', $config['bundles']);
        static::assertEquals('https://extension-api.test', $config['bundles']['AdminExtensionApiPlugin']['baseUrl']);
        static::assertEquals('plugin', $config['bundles']['AdminExtensionApiPlugin']['type']);

        static::assertArrayHasKey('AdminExtensionApiPluginWithLocalEntryPoint', $config['bundles']);
        static::assertStringContainsString(
            '/admin/adminextensionapipluginwithlocalentrypoint/index.html',
            $config['bundles']['AdminExtensionApiPluginWithLocalEntryPoint']['baseUrl'],
        );
        static::assertEquals('plugin', $config['bundles']['AdminExtensionApiPluginWithLocalEntryPoint']['type']);

        static::assertArrayHasKey('AdminExtensionApiApp', $config['bundles']);
        static::assertEquals('https://app-admin.test', $config['bundles']['AdminExtensionApiApp']['baseUrl']);
        static::assertEquals('app', $config['bundles']['AdminExtensionApiApp']['type']);
    }

    public function testFlowActionsRoute(): void
    {
        $url = '/api/_info/flow-actions.json';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        $expected = [
            [
                'name' => 'action.add.order.tag',
                'requirements' => [
                    'orderAware',
                ],
                'extensions' => [],
                'delayable' => true,
            ],
        ];

        foreach ($expected as $action) {
            $actualActions = array_values(array_filter($response, fn ($x) => $x['name'] === $action['name']));
            static::assertNotEmpty($actualActions, 'Event with name "' . $action['name'] . '" not found');
            static::assertCount(1, $actualActions);
            static::assertEquals($action, $actualActions[0]);
        }
    }

    public function testFlowActionRouteHasAppFlowActions(): void
    {
        $aclRoleId = Uuid::randomHex();
        $this->createAclRole($aclRoleId);

        $appId = Uuid::randomHex();
        $this->createApp($appId, $aclRoleId);

        $flowAppId = Uuid::randomHex();
        $this->createAppFlowAction($flowAppId, $appId);

        $url = '/api/_info/flow-actions.json';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $expected = [
            [
                'name' => 'telegram.send.message',
                'requirements' => [
                    'orderaware',
                ],
                'extensions' => [],
                'delayable' => true,
            ],
        ];

        foreach ($expected as $action) {
            $actualActions = array_values(array_filter($response, fn ($x) => $x['name'] === $action['name']));
            static::assertNotEmpty($actualActions, 'Event with name "' . $action['name'] . '" not found');
            static::assertCount(1, $actualActions);
            static::assertEquals($action, $actualActions[0]);
        }
    }

    public function testMailAwareBusinessEventRoute(): void
    {
        $url = '/api/_info/events.json';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $response = json_decode($content, true);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        foreach ($response as $event) {
            if ($event['name'] === 'mail.after.create.message' || $event['name'] === 'mail.before.send' || $event['name'] === 'mail.sent') {
                static::assertFalse(\in_array('Cicada\Core\Framework\Event\MailAware', $event['aware'], true));

                continue;
            }

            static::assertContains('Cicada\Core\Framework\Event\MailAware', $event['aware'], $event['name']);
            static::assertNotContains('Cicada\Core\Framework\Event\MailActionInterface', $event['aware'], $event['name']);
        }
    }

    public function testFlowBusinessEventRouteHasAppFlowEvents(): void
    {
        $aclRoleId = Uuid::randomHex();
        $this->createAclRole($aclRoleId);

        $appId = Uuid::randomHex();
        $this->createApp($appId, $aclRoleId);

        $flowAppId = Uuid::randomHex();
        $this->createAppFlowEvent($flowAppId, $appId);

        $url = '/api/_info/events.json';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);

        $response = json_decode($content, true);

        $expected = [
            [
                'name' => 'customer.wishlist',
                'aware' => [
                    'mailAware',
                    'customerAware',
                ],
                'data' => [],
                'class' => 'Cicada\Core\Framework\App\Event\CustomAppEvent',
                'extensions' => [],
            ],
        ];

        foreach ($expected as $event) {
            $actualEvent = array_values(array_filter($response, function ($x) use ($event) {
                return $x['name'] === $event['name'];
            }));

            static::assertNotEmpty($actualEvent, 'Event with name "' . $event['name'] . '" not found');
            static::assertCount(1, $actualEvent);
            static::assertEquals($event, $actualEvent[0]);
        }
    }

    public function testFetchApiRoutes(): void
    {
        $client = $this->getBrowser();
        $client->request('GET', '/api/_info/routes');

        $content = $client->getResponse()->getContent();
        static::assertNotFalse($content);
        static::assertJson($content);
        static::assertSame(200, $client->getResponse()->getStatusCode());

        $routes = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        foreach ($routes['endpoints'] as $route) {
            static::assertArrayHasKey('path', $route);
            static::assertArrayHasKey('methods', $route);
        }
    }

    private function createApp(string $appId, string $aclRoleId): void
    {
        $this->connection->insert('app', [
            'id' => Uuid::fromHexToBytes($appId),
            'name' => 'flowbuilderactionapp',
            'active' => 1,
            'path' => 'custom/apps/flowbuilderactionapp',
            'version' => '1.0.0',
            'configurable' => 0,
            'app_secret' => 'appSecret',
            'acl_role_id' => Uuid::fromHexToBytes($aclRoleId),
            'integration_id' => $this->getIntegrationId(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function createAppFlowAction(string $flowAppId, string $appId): void
    {
        $this->connection->insert('app_flow_action', [
            'id' => Uuid::fromHexToBytes($flowAppId),
            'app_id' => Uuid::fromHexToBytes($appId),
            'name' => 'telegram.send.message',
            'badge' => 'Telegram',
            'url' => 'https://example.xyz',
            'delayable' => true,
            'requirements' => json_encode(['orderaware']),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function createAppFlowEvent(string $flowAppId, string $appId): void
    {
        $this->connection->insert('app_flow_event', [
            'id' => Uuid::fromHexToBytes($flowAppId),
            'app_id' => Uuid::fromHexToBytes($appId),
            'name' => 'customer.wishlist',
            'aware' => json_encode(['mailAware', 'customerAware']),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function getIntegrationId(): string
    {
        $integrationId = Uuid::randomBytes();

        $this->connection->insert('integration', [
            'id' => $integrationId,
            'access_key' => 'test',
            'secret_access_key' => 'test',
            'label' => 'test',
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        return $integrationId;
    }

    private function createAclRole(string $aclRoleId): void
    {
        $this->connection->insert('acl_role', [
            'id' => Uuid::fromHexToBytes($aclRoleId),
            'name' => 'aclTest',
            'privileges' => json_encode(['users_and_permissions.viewer']),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}

/**
 * @internal
 */
class AdminExtensionApiPlugin extends Plugin
{
    public function getAdminBaseUrl(): ?string
    {
        return 'https://extension-api.test';
    }
}

/**
 * @internal
 */
class AdminExtensionApiPluginWithLocalEntryPoint extends Plugin
{
    public function getPath(): string
    {
        $reflected = new \ReflectionObject($this);

        return \dirname($reflected->getFileName() ?: '') . '/Fixtures/AdminExtensionApiPluginWithLocalEntryPoint';
    }
}
