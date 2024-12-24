<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Storefront;
use Cicada\Storefront\Theme\Exception\ThemeAssignmentException;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginRegistryInterface;
use Cicada\Storefront\Theme\ThemeEntity;
use Cicada\Storefront\Theme\ThemeLifecycleHandler;
use Cicada\Storefront\Theme\ThemeLifecycleService;
use Cicada\Storefront\Theme\ThemeSalesChannel;
use Cicada\Storefront\Theme\ThemeSalesChannelCollection;
use Cicada\Storefront\Theme\ThemeService;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\InheritanceWithConfig\InheritanceWithConfig;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\PluginWithAdditionalBundles\PluginWithAdditionalBundles;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\SimplePlugin\SimplePlugin;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\SimplePluginWithoutCompilation\SimplePluginWithoutCompilation;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\SimpleTheme\SimpleTheme;

/**
 * @internal
 */
#[CoversClass(ThemeLifecycleHandler::class)]
class ThemeLifecycleHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MockObject&ThemeService $themeServiceMock;

    private MockObject&StorefrontPluginRegistryInterface $configurationRegistryMock;

    private ThemeLifecycleHandler $themeLifecycleHandler;

    private AbstractStorefrontPluginConfigurationFactory $configFactory;

    protected function setUp(): void
    {
        $this->themeServiceMock = $this->createMock(ThemeService::class);

        $this->configurationRegistryMock = $this->createMock(StorefrontPluginRegistryInterface::class);

        $this->themeLifecycleHandler = new ThemeLifecycleHandler(
            static::getContainer()->get(ThemeLifecycleService::class),
            $this->themeServiceMock,
            static::getContainer()->get('theme.repository'),
            $this->configurationRegistryMock,
            static::getContainer()->get(Connection::class)
        );

        $this->configFactory = static::getContainer()->get(StorefrontPluginConfigurationFactory::class);

        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM `theme_sales_channel`');
        $this->assignThemeToDefaultSalesChannel();
    }

    public function testHandleThemeInstallOrUpdateWillRecompileThemeIfNecessary(): void
    {
        $installConfig = $this->configFactory->createFromBundle(new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'));

        $this->themeServiceMock->expects(static::once())
            ->method('compileTheme')
            ->with(
                TestDefaults::SALES_CHANNEL,
                static::isType('string'),
                static::isInstanceOf(Context::class),
                static::callback(fn (StorefrontPluginConfigurationCollection $configs): bool => $configs->count() === 2)
            );

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $installConfig,
        ]);

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate($installConfig, $configs, Context::createDefaultContext());
    }

    public function testHandleThemeInstallOrUpdateWillRecompilePluginWithSubBundles(): void
    {
        $installConfig = $this->configFactory->createFromBundle(new PluginWithAdditionalBundles(true, __DIR__ . '/fixtures/PluginWithSubBundles'));

        $this->themeServiceMock->expects(static::once())
            ->method('compileTheme')
            ->with(
                TestDefaults::SALES_CHANNEL,
                static::isType('string'),
                static::isInstanceOf(Context::class),
                static::callback(fn (StorefrontPluginConfigurationCollection $configs): bool => $configs->count() === 2)
            );

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $installConfig,
        ]);

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate($installConfig, $configs, Context::createDefaultContext());
    }

    public function testHandleThemeInstallOrUpdateWithInheritance(): void
    {
        $installConfig = $this->configFactory->createFromBundle(new InheritanceWithConfig());

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $installConfig,
        ]);

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate($installConfig, $configs, Context::createDefaultContext());

        /** @var EntityRepository $themeRepository */
        $themeRepository = static::getContainer()->get('theme.repository');
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'ThemeWithMultiInheritance'));
        $criteria->addAssociation('parentThemes');
        /** @var ThemeEntity $theme */
        $theme = $themeRepository->search($criteria, $context)->first();
    }

    public function testHandleThemeInstallOrUpdateWillRecompileOnlyTouchedTheme(): void
    {
        $salesChannelId = $this->createSalesChannel();
        $themeId = $this->createTheme('SimpleTheme', $salesChannelId);
        $installConfig = $this->configFactory->createFromBundle(new SimpleTheme());
        $installConfig->setStyleFiles(FileCollection::createFromArray(['onlyForFile']));

        $this->themeServiceMock->expects(static::once())
            ->method('compileThemeById')
            ->with(
                $themeId,
                static::isInstanceOf(Context::class),
                static::callback(fn (StorefrontPluginConfigurationCollection $configs): bool => $configs->count() === 2)
            );

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $installConfig,
        ]);

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate($installConfig, $configs, Context::createDefaultContext());
    }

    public function testHandleThemeUninstallWillRecompileThemeIfNecessary(): void
    {
        $uninstalledConfig = $this->configFactory->createFromBundle(new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'));

        $this->themeServiceMock->expects(static::once())
            ->method('compileTheme')
            ->with(
                TestDefaults::SALES_CHANNEL,
                static::isType('string'),
                static::isInstanceOf(Context::class),
                static::callback(fn (StorefrontPluginConfigurationCollection $configs): bool => $configs->count() === 1 && (
                    (
                        $configs->first() instanceof StorefrontPluginConfiguration
                        ? $configs->first()->getTechnicalName()
                        : ''
                    ) === 'Storefront'
                ))
            );

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $uninstalledConfig,
        ]);

        $this->configurationRegistryMock->expects(static::once())
            ->method('getConfigurations')
            ->willReturn($configs);

        $this->themeLifecycleHandler->handleThemeUninstall($uninstalledConfig, Context::createDefaultContext());
    }

    public function testHandleThemeUninstallWillNotRecompileThemeIfNotNecessary(): void
    {
        $uninstalledConfig = $this->configFactory->createFromBundle(new SimplePluginWithoutCompilation());

        $this->themeServiceMock->expects(static::never())
            ->method('compileTheme');

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $uninstalledConfig,
        ]);

        $this->configurationRegistryMock->expects(static::once())
            ->method('getConfigurations')
            ->willReturn($configs);

        $this->themeLifecycleHandler->handleThemeUninstall($uninstalledConfig, Context::createDefaultContext());
    }

    public function testHandleThemeUninstallWillThrowExceptionIfThemeIsStillInUse(): void
    {
        $uninstalledConfig = $this->configFactory->createFromBundle(new SimpleTheme());
        $uninstalledConfig->setStyleFiles(new FileCollection());
        $uninstalledConfig->setScriptFiles(new FileCollection());

        $configs = new StorefrontPluginConfigurationCollection([
            $this->configFactory->createFromBundle(new Storefront()),
            $uninstalledConfig,
        ]);

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate($uninstalledConfig, $configs, Context::createDefaultContext());
        $this->assignThemeToDefaultSalesChannel('SimpleTheme');

        $wasThrown = false;

        $scCollection = new ThemeSalesChannelCollection();
        $scCollection->add(new ThemeSalesChannel(Uuid::randomHex(), Uuid::randomHex()));
        $this->themeServiceMock->expects(static::once())
            ->method('getThemeDependencyMapping')
            ->willReturn($scCollection);

        try {
            $this->themeLifecycleHandler->handleThemeUninstall($uninstalledConfig, Context::createDefaultContext());
        } catch (ThemeAssignmentException $e) {
            static::assertEquals(
                [TestDefaults::SALES_CHANNEL],
                array_keys($e->getAssignedSalesChannels() ?? [])
            );
            $wasThrown = true;
        }

        static::assertTrue($wasThrown);
    }

    private function assignThemeToDefaultSalesChannel(?string $themeName = null): void
    {
        $themeRepository = static::getContainer()->get('theme.repository');
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        if ($themeName) {
            $criteria->addFilter(new EqualsFilter('technicalName', $themeName));
        }

        $themeId = $themeRepository->searchIds($criteria, $context)->firstId();

        $themeRepository->update([
            [
                'id' => $themeId,
                'salesChannels' => [
                    [
                        'id' => TestDefaults::SALES_CHANNEL,
                    ],
                ],
            ],
        ], $context);
    }

    private function createTheme(string $name, string $salesChannelId): string
    {
        $id = Uuid::randomHex();

        $repository = static::getContainer()->get('theme.repository');

        $repository->create([
            [
                'id' => $id,
                'technicalName' => $name,
                'name' => $name,
                'author' => 'test',
                'active' => true,
                'salesChannels' => [
                    [
                        'id' => $salesChannelId,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        return $id;
    }

    private function createSalesChannel(): string
    {
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');

        $id = Uuid::randomHex();
        $payload = [[
            'id' => $id,
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'currencyId' => Defaults::CURRENCY,
            'active' => true,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $this->getValidShippingMethodId(),
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $this->getValidCountryId(),
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'shippingMethods' => [['id' => $this->getValidShippingMethodId()]],
            'paymentMethods' => [['id' => $this->getValidPaymentMethodId()]],
            'countries' => [['id' => $this->getValidCountryId()]],
            'name' => 'first sales-channel',
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
        ]];

        $salesChannelRepository->create($payload, Context::createDefaultContext());

        return $id;
    }
}
