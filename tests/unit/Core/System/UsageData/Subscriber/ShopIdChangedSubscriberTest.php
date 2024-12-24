<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\Subscriber;

use Cicada\Core\Framework\App\ShopId\ShopIdChangedEvent;
use Cicada\Core\Framework\App\ShopId\ShopIdDeletedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\StoreService;
use Cicada\Core\System\UsageData\Consent\BannerService;
use Cicada\Core\System\UsageData\Consent\ConsentService;
use Cicada\Core\System\UsageData\Consent\ConsentState;
use Cicada\Core\System\UsageData\Services\EntityDispatchService;
use Cicada\Core\System\UsageData\Subscriber\ShopIdChangedSubscriber;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('data-services')]
#[CoversClass(ShopIdChangedSubscriber::class)]
class ShopIdChangedSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertEquals([
            ShopIdDeletedEvent::class => 'handleShopIdDeleted',
            ShopIdChangedEvent::class => 'handleShopIdChanged',
        ], ShopIdChangedSubscriber::getSubscribedEvents());
    }

    public function testResetConsentWhenShopIdIsDeleted(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            ConsentService::SYSTEM_CONFIG_KEY_CONSENT_STATE => ConsentState::ACCEPTED->value,
        ]);

        $bannerService = $this->createMock(BannerService::class);
        $bannerService->expects(static::once())
            ->method('resetIsBannerHiddenForAllUsers');

        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::once())
            ->method('resetLastRunDateForAllEntities');

        $shopIdChangedSubscriber = new ShopIdChangedSubscriber(
            $bannerService,
            $systemConfigService,
            $entityDispatchService
        );

        $shopIdChangedSubscriber->handleShopIdDeleted(new ShopIdDeletedEvent());

        static::assertNull($systemConfigService->get(ConsentService::SYSTEM_CONFIG_KEY_CONSENT_STATE));
    }

    public function testHandleShopIdChangedRevokesAndResetsConsent(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            ConsentService::SYSTEM_CONFIG_KEY_CONSENT_STATE => ConsentState::ACCEPTED->value,
            StoreService::CONFIG_KEY_STORE_LICENSE_DOMAIN => 'license.host',
        ]);

        $bannerService = $this->createMock(BannerService::class);
        $bannerService->expects(static::once())
            ->method('resetIsBannerHiddenForAllUsers');

        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::once())
            ->method('resetLastRunDateForAllEntities');

        $shopIdChangedSubscriber = new ShopIdChangedSubscriber(
            $bannerService,
            $systemConfigService,
            $entityDispatchService
        );

        $shopIdChangedSubscriber->handleShopIdChanged(new ShopIdChangedEvent(
            [
                'value' => 'newShopId',
                'app_url' => 'newAppUrl',
            ],
            [
                'value' => 'oldShopId',
                'app_url' => 'oldAppUrl',
            ],
        ));
    }

    public function testHandleShopIdChangedDoesNothingIfOldShopIdIsNull(): void
    {
        $bannerService = $this->createMock(BannerService::class);
        $bannerService->expects(static::never())
            ->method('resetIsBannerHiddenForAllUsers');

        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::never())
            ->method('resetLastRunDateForAllEntities');

        $shopIdChangedSubscriber = new ShopIdChangedSubscriber(
            $bannerService,
            new StaticSystemConfigService([]),
            $entityDispatchService
        );

        $shopIdChangedSubscriber->handleShopIdChanged(new ShopIdChangedEvent(
            [
                'value' => 'newShopId',
                'app_url' => 'newAppUrl',
            ],
            null
        ));
    }

    public function testHandleShopIdDoesNothingIfOldShopIdWasSet(): void
    {
        $bannerService = $this->createMock(BannerService::class);
        $bannerService->expects(static::never())
            ->method('resetIsBannerHiddenForAllUsers');

        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::never())
            ->method('resetLastRunDateForAllEntities');

        $shopIdChangedSubscriber = new ShopIdChangedSubscriber(
            $bannerService,
            new StaticSystemConfigService([]),
            $entityDispatchService
        );

        $shopIdChangedSubscriber->handleShopIdChanged(new ShopIdChangedEvent(
            [
                'value' => 'newShopId',
                'app_url' => 'newAppUrl',
            ],
            [
                'value' => 'newShopId',
                'app_url' => 'newAppUrl',
            ],
        ));
    }
}
