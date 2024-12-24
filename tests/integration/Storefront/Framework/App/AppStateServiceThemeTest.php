<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\App;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\App\AppStateService;
use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Theme\Exception\ThemeAssignmentException;
use Cicada\Storefront\Theme\ThemeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;

/**
 * @internal
 */
class AppStateServiceThemeTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private ThemeService $themeService;

    private EntityRepository $appRepo;

    private EntityRepository $themeRepo;

    private AppStateService $appStateService;

    private TraceableEventDispatcher $eventDispatcher;

    private EntityRepository $templateRepo;

    protected function setUp(): void
    {
        $this->themeService = static::getContainer()->get(ThemeService::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $this->appRepo = static::getContainer()->get('app.repository');
        $this->themeRepo = static::getContainer()->get('theme.repository', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $this->templateRepo = static::getContainer()->get('app_template.repository');
        $this->appStateService = static::getContainer()->get(AppStateService::class);
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');
    }

    public function testAppWithAThemeInUseCannotBeDeactivated(): void
    {
        $context = Context::createDefaultContext();
        $this->loadAppsFromDir(__DIR__ . '/../../Theme/fixtures/Apps/theme');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'SwagTheme'));
        $themeId = $this->themeRepo->searchIds($criteria, $context)->firstId();
        static::assertIsString($themeId);
        $salesChannelId = $this->createSalesChannel();

        $this->themeService->assignTheme($themeId, $salesChannelId, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'SwagTheme'));
        $appId = $this->appRepo->searchIds($criteria, $context)->firstId();
        static::assertIsString($appId);

        $this->expectException(ThemeAssignmentException::class);
        $this->appStateService->deactivateApp($appId, $context);
    }

    public function testAppWithAChildThemeInUseCannotBeDeactivated(): void
    {
        $context = Context::createDefaultContext();
        $this->loadAppsFromDir(__DIR__ . '/../../Theme/fixtures/Apps/theme');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'SwagTheme'));
        $themeId = $this->themeRepo->searchIds($criteria, $context)->firstId();

        $childId = Uuid::randomHex();
        $childTheme = [
            'id' => $childId,
            'name' => 'child',
            'parentThemeId' => $themeId,
            'author' => 'author',
            'active' => true,
        ];

        $this->themeRepo->upsert([[
            'id' => $themeId,
            'dependentThemes' => [$childTheme],
        ]], $context);

        $salesChannelId = $this->createSalesChannel();

        $this->themeService->assignTheme($childId, $salesChannelId, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'SwagTheme'));
        $appId = $this->appRepo->searchIds($criteria, $context)->firstId();
        static::assertIsString($appId);

        $this->expectException(ThemeAssignmentException::class);
        $this->appStateService->deactivateApp($appId, $context);
    }

    public function testAppWithAThemeCanBeDeactivated(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../../Theme/fixtures/Apps/theme');
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'SwagTheme'));
        $appId = $this->appRepo->searchIds($criteria, $context)->firstId();
        static::assertIsString($appId);

        $eventWasReceived = false;
        $onAppDeactivation = function (AppDeactivatedEvent $event) use (&$eventWasReceived, $appId, $context): void {
            $eventWasReceived = true;
            static::assertEquals($appId, $event->getApp()->getId());
            static::assertEquals($context, $event->getContext());
        };
        $this->eventDispatcher->addListener(AppDeactivatedEvent::class, $onAppDeactivation);

        $this->appStateService->deactivateApp($appId, $context);

        static::assertTrue($eventWasReceived);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));
        $criteria->addFilter(new EqualsFilter('active', true));

        static::assertEquals(0, $this->templateRepo->search($criteria, $context)->getTotal());

        $this->eventDispatcher->removeListener(AppDeactivatedEvent::class, $onAppDeactivation);
    }

    public function testAppWithAThemeCanBeActivated(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../../Theme/fixtures/Apps/theme', false);
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'SwagTheme'));
        $appId = $this->appRepo->searchIds($criteria, $context)->firstId();
        static::assertIsString($appId);

        $eventWasReceived = false;
        $onAppActivation = function (AppActivatedEvent $event) use (&$eventWasReceived, $appId, $context): void {
            $eventWasReceived = true;
            static::assertEquals($appId, $event->getApp()->getId());
            static::assertEquals($context, $event->getContext());
        };
        $this->eventDispatcher->addListener(AppActivatedEvent::class, $onAppActivation);

        $this->appStateService->activateApp($appId, $context);

        static::assertTrue($eventWasReceived);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));
        $criteria->addFilter(new EqualsFilter('active', true));

        // We expect 1 storefront twig template and svg image to be stored in the DB
        $expectedTemplates = 2;
        static::assertEquals($expectedTemplates, $this->templateRepo->search($criteria, $context)->getTotal());

        $this->eventDispatcher->removeListener(AppActivatedEvent::class, $onAppActivation);
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
