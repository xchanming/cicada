<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer\Controller;

use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Installer\Configuration\AdminConfigurationService;
use Cicada\Core\Installer\Configuration\EnvConfigWriter;
use Cicada\Core\Installer\Configuration\ShopConfigurationService;
use Cicada\Core\Installer\Controller\ShopConfigurationController;
use Cicada\Core\Installer\Database\BlueGreenDeploymentService;
use Cicada\Core\Maintenance\System\Service\DatabaseConnectionFactory;
use Cicada\Core\Maintenance\System\Struct\DatabaseConnectionInformation;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(ShopConfigurationController::class)]
class ShopConfigurationControllerTest extends TestCase
{
    use EnvTestBehaviour;
    use InstallerControllerTestTrait;

    private MockObject&Environment $twig;

    private MockObject&RouterInterface $router;

    private Connection&MockObject $connection;

    private MockObject&EnvConfigWriter $envConfigWriter;

    private MockObject&ShopConfigurationService $shopConfigService;

    private MockObject&AdminConfigurationService $adminConfigService;

    private ShopConfigurationController $controller;

    /**
     * @var TranslatorInterface&MockObject
     */
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->connection = $this->createMock(Connection::class);
        $connectionFactory = $this->createMock(DatabaseConnectionFactory::class);
        $connectionFactory->method('getConnection')->willReturn($this->connection);

        $this->envConfigWriter = $this->createMock(EnvConfigWriter::class);
        $this->shopConfigService = $this->createMock(ShopConfigurationService::class);
        $this->adminConfigService = $this->createMock(AdminConfigurationService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->controller = new ShopConfigurationController(
            $connectionFactory,
            $this->envConfigWriter,
            $this->shopConfigService,
            $this->adminConfigService,
            $this->translator,
            ['zh' => 'zh-CN', 'en' => 'en-GB'],
            ['CNY', 'USD']
        );
        $this->controller->setContainer($this->getInstallerContainer($this->twig, ['router' => $this->router]));
    }

    public function testGetConfigurationRoute(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set(DatabaseConnectionInformation::class, new DatabaseConnectionInformation());
        $session->set(BlueGreenDeploymentService::ENV_NAME, true);
        $request->setMethod('GET');
        $request->setSession($session);
        $request->attributes->set('_locale', 'zh');

        $this->connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['iso3' => 'CHN', 'iso' => 'CN'],
            ]);

        $this->translator->method('trans')->willReturnCallback(fn (string $key): string => $key);

        $this->twig->expects(static::once())->method('render')
            ->with(
                '@Installer/installer/shop-configuration.html.twig',
                array_merge($this->getDefaultViewParams(), [
                    'error' => null,
                    'countryIsos' => [
                        ['iso3' => 'CHN', 'default' => true, 'translated' => 'cicada.installer.select_country_chn'],
                    ],
                    'currencyIsos' => ['CNY', 'USD'],
                    'languageIsos' => ['zh' => 'zh-CN', 'en' => 'en-GB'],
                    'parameters' => ['config_shop_language' => 'zh-CN'],
                ])
            )
            ->willReturn('config');

        $response = $this->controller->shopConfiguration($request);
        static::assertSame('config', $response->getContent());
    }

    public function testGetConfigurationRouteRedirectsIfSessionIsExpired(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setMethod('GET');
        $request->setSession($session);

        $this->router->expects(static::once())->method('generate')
            ->with('installer.database-configuration', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/installer/database-configuration');

        $this->twig->expects(static::never())->method('render');

        $response = $this->controller->shopConfiguration($request);
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/installer/database-configuration', $response->getTargetUrl());
    }

    public function testPostConfigurationRoute(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setMethod('POST');
        $connectionInfo = new DatabaseConnectionInformation();
        $session->set(DatabaseConnectionInformation::class, $connectionInfo);
        $session->set(BlueGreenDeploymentService::ENV_NAME, true);
        $request->setSession($session);
        $request->attributes->set('_locale', 'zh');

        $request->request->set('config_admin_email', 'test@test.com');
        $request->request->set('config_admin_username', 'admin');
        $request->request->set('config_admin_name', 'first');
        $request->request->set('config_admin_password', '12345678');
        $request->request->set('config_shop_language', 'zh-CN');
        $request->request->set('config_shop_currency', 'CNY');
        $request->request->set('config_shop_country', 'CHN');
        $request->request->set('config_shopName', 'shop');
        $request->request->set('config_mail', 'info@test.com');
        $request->request->set('available_currencies', ['CNY', 'USD']);

        $this->setEnvVars([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'localhost',
            'SCRIPT_NAME' => '/shop/index.php',
        ]);

        $expectedShopInfo = [
            'name' => 'shop',
            'locale' => 'zh-CN',
            'currency' => 'CNY',
            'additionalCurrencies' => ['CNY', 'USD'],
            'country' => 'CHN',
            'email' => 'info@test.com',
            'host' => 'localhost',
            'schema' => 'https',
            'basePath' => '/shop',
            'blueGreenDeployment' => true,
        ];

        $this->envConfigWriter->expects(static::once())->method('writeConfig')->with($connectionInfo, $expectedShopInfo);
        $this->shopConfigService->expects(static::once())->method('updateShop')->with($expectedShopInfo, $this->connection);

        $expectedAdmin = [
            'email' => 'test@test.com',
            'username' => 'admin',
            'name' => 'first',
            'password' => '12345678',
            'locale' => 'zh-CN',
        ];
        $this->adminConfigService->expects(static::once())->method('createAdmin')->with($expectedAdmin, $this->connection);

        $this->translator->method('trans')->willReturnCallback(fn (string $key): string => $key);

        $this->router->expects(static::once())->method('generate')
            ->with('installer.finish', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/installer/finish');

        $this->twig->expects(static::never())->method('render');

        $response = $this->controller->shopConfiguration($request);
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/installer/finish', $response->getTargetUrl());

        static::assertFalse($session->has(DatabaseConnectionInformation::class));
        static::assertEquals($expectedAdmin, $session->get('ADMIN_USER'));
    }

    public function testPostConfigurationRouteOnError(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set(DatabaseConnectionInformation::class, new DatabaseConnectionInformation());
        $session->set(BlueGreenDeploymentService::ENV_NAME, true);
        $request->setMethod('POST');
        $request->setSession($session);
        $request->attributes->set('_locale', 'zh');

        $this->setEnvVars([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'localhost',
            'SCRIPT_NAME' => '/shop/index.php',
        ]);

        $this->connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['iso3' => 'CHN', 'iso' => 'CN'],
            ]);

        $this->envConfigWriter->expects(static::once())->method('writeConfig')->willThrowException(new \Exception('Test Exception'));

        $this->translator->method('trans')->willReturnCallback(fn (string $key): string => $key);

        $this->twig->expects(static::once())->method('render')
            ->with(
                '@Installer/installer/shop-configuration.html.twig',
                array_merge($this->getDefaultViewParams(), [
                    'error' => 'Test Exception',
                    'countryIsos' => [
                        ['iso3' => 'CHN', 'default' => true, 'translated' => 'cicada.installer.select_country_chn'],
                    ],
                    'currencyIsos' => ['CNY', 'USD'],
                    'languageIsos' => ['zh' => 'zh-CN', 'en' => 'en-GB'],
                    'parameters' => ['config_shop_language' => 'zh-CN'],
                ])
            )
            ->willReturn('config');

        $response = $this->controller->shopConfiguration($request);
        static::assertSame('config', $response->getContent());
    }

    public function testGetConfigurationCountryIsosSortedByAlphabetical(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set(DatabaseConnectionInformation::class, new DatabaseConnectionInformation());
        $session->set(BlueGreenDeploymentService::ENV_NAME, true);
        $request->setMethod('POST');
        $request->setSession($session);
        $request->attributes->set('_locale', 'zh');

        $this->setEnvVars([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'localhost',
            'SCRIPT_NAME' => '/shop/index.php',
        ]);

        // in non-alphabetical order
        $countries = [
            ['iso3' => 'GBR', 'iso' => 'GB'],
            ['iso3' => 'BGR', 'iso' => 'BG'],
            ['iso3' => 'EST', 'iso' => 'EE'],
            ['iso3' => 'HRV', 'iso' => 'HR'],
            ['iso3' => 'DEU', 'iso' => 'DE'],
        ];

        $translations = [
            'cicada.installer.select_country_gbr' => 'Great Britain',
            'cicada.installer.select_country_bgr' => 'Bulgaria',
            'cicada.installer.select_country_est' => 'Estonia',
            'cicada.installer.select_country_hrv' => 'Croatia',
            'cicada.installer.select_country_deu' => 'Germany',
        ];

        $this->connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn($countries);

        $this->envConfigWriter->expects(static::once())->method('writeConfig')->willThrowException(new \Exception('Test Exception'));

        $this->translator->method('trans')->willReturnCallback(fn (string $key): string => $translations[$key]);

        $this->twig->expects(static::once())->method('render')->willReturnCallback(function (string $view, array $parameters): string {
            static::assertEquals('@Installer/installer/shop-configuration.html.twig', $view);
            static::assertArrayHasKey('countryIsos', $parameters);

            $countryIsos = $parameters['countryIsos'];

            static::assertSame([
                'Bulgaria',
                'Croatia',
                'Estonia',
                'Germany',
                'Great Britain',
            ], array_column($countryIsos, 'translated'));

            return '';
        });

        $this->controller->shopConfiguration($request);
    }
}
