<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\ShopId;

use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\AppSystemTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ShopIdProviderTest extends TestCase
{
    use AppSystemTestBehaviour;
    use EnvTestBehaviour;
    use IntegrationTestBehaviour;

    private ShopIdProvider $shopIdProvider;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->shopIdProvider = static::getContainer()->get(ShopIdProvider::class);
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
    }

    public function testGetShopIdWithoutStoredShopId(): void
    {
        $shopId = $this->shopIdProvider->getShopId();

        static::assertEquals([
            'app_url' => $_SERVER['APP_URL'],
            'value' => $shopId,
        ], $this->systemConfigService->get(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY));
    }

    public function testGetShopIdReturnsSameIdOnMultipleCalls(): void
    {
        $firstShopId = $this->shopIdProvider->getShopId();
        $secondShopId = $this->shopIdProvider->getShopId();

        static::assertSame($firstShopId, $secondShopId);

        static::assertEquals([
            'app_url' => $_SERVER['APP_URL'],
            'value' => $firstShopId,
        ], $this->systemConfigService->get(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY));
    }

    public function testGetShopIdThrowsIfAppUrlIsChangedAndAppsArePresent(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../Manifest/_fixtures/test');

        $this->shopIdProvider->getShopId();

        $this->setEnvVars([
            'APP_URL' => 'http://test.com',
        ]);

        try {
            $this->shopIdProvider->getShopId();
            static::fail('expected AppUrlChangeDetectedException was not thrown.');
        } catch (AppUrlChangeDetectedException) {
            // exception is expected
        }
    }

    public function testGetShopIdUpdatesItselfIfAppUrlIsChangedAndNoAppsArePresent(): void
    {
        $firstShopId = $this->shopIdProvider->getShopId();

        $this->setEnvVars([
            'APP_URL' => 'http://test.com',
        ]);

        $secondShopId = $this->shopIdProvider->getShopId();

        static::assertEquals([
            'app_url' => 'http://test.com',
            'value' => $firstShopId,
        ], $this->systemConfigService->get(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY));

        static::assertSame($firstShopId, $secondShopId);
    }

    public function testItRemovesTheAppUrlChangedMarkerIfOutdated(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../Manifest/_fixtures/test');

        $this->shopIdProvider->getShopId();

        $this->setEnvVars([
            'APP_URL' => 'http://test.com',
        ]);

        try {
            $this->shopIdProvider->getShopId();
            static::fail('expected AppUrlChangeDetectedException was not thrown.');
        } catch (AppUrlChangeDetectedException) {
            // exception is expected
        }

        $this->resetEnvVars();

        $this->shopIdProvider->getShopId();
    }
}
