<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Hmac;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Hmac\QuerySigner;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(QuerySigner::class)]
#[Package('framework')]
class QuerySignerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private AppEntity $app;

    private QuerySigner $querySigner;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->app = new AppEntity();
        $this->app->setName('TestApp');
        $this->app->setId('app-id');
        $this->app->setAppSecret('lksf#$osck$FSFDSF#$#F43jjidjsfisj-333');

        $this->querySigner = static::getContainer()->get(QuerySigner::class);
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
    }

    public function testSignUri(): void
    {
        $signedUri = $this->querySigner->signUri('http://app.url/?foo=bar', $this->app, Context::createDefaultContext());
        parse_str($signedUri->getQuery(), $signedQuery);

        static::assertArrayHasKey('shop-id', $signedQuery);
        $shopConfig = $this->systemConfigService->get(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY);
        static::assertIsArray($shopConfig);
        static::assertArrayHasKey('value', $shopConfig);
        $shopId = $shopConfig['value'];
        static::assertIsString($shopId);
        static::assertSame($shopId, $signedQuery['shop-id']);

        static::assertArrayHasKey('shop-url', $signedQuery);
        static::assertArrayHasKey('app_url', $shopConfig);
        $shopUrl = $shopConfig['app_url'];
        static::assertIsString($shopUrl);
        static::assertSame($shopUrl, $signedQuery['shop-url']);

        static::assertArrayHasKey('timestamp', $signedQuery);

        static::assertArrayHasKey('sw-version', $signedQuery);
        static::assertSame(static::getContainer()->getParameter('kernel.cicada_version'), $signedQuery['sw-version']);

        static::assertArrayHasKey('sw-context-language', $signedQuery);
        static::assertSame(Context::createDefaultContext()->getLanguageId(), $signedQuery['sw-context-language']);

        static::assertArrayHasKey('sw-user-language', $signedQuery);
        static::assertSame('zh-CN', $signedQuery['sw-user-language']);

        static::assertNotNull($this->app->getAppSecret());

        static::assertArrayHasKey('cicada-shop-signature', $signedQuery);
        static::assertSame(
            \hash_hmac('sha256', Uri::withoutQueryValue($signedUri, 'cicada-shop-signature')->getQuery(), $this->app->getAppSecret()),
            $signedQuery['cicada-shop-signature']
        );
    }
}
