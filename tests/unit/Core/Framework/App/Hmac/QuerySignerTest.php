<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Hmac;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Hmac\QuerySigner;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\LocaleProvider;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(QuerySigner::class)]
class QuerySignerTest extends TestCase
{
    public function testSignUri(): void
    {
        $inAppPurchase = StaticInAppPurchaseFactory::createWithFeatures(['extension-1' => ['purchase-1', 'purchase-2'], 'extension-2' => ['purchase-3']]);

        $context = new Context(new AdminApiSource(null));

        $localeProvider = $this->createMock(LocaleProvider::class);
        $localeProvider
            ->expects(static::once())
            ->method('getLocaleFromContext')
            ->with($context)
            ->willReturn('en-GB');

        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->expects(static::once())
            ->method('getShopId')
            ->willReturn('shopId');

        $app = new AppEntity();
        $app->setName('extension-1');
        $app->setAppSecret('devSecret');
        $app->setId(Uuid::randomHex());

        $querySigner = new QuerySigner('http://shop.url', '1.0.0', $localeProvider, $shopIdProvider, $inAppPurchase);
        $signedQuery = $querySigner->signUri('http://app.url/?foo=bar', $app, $context);

        \parse_str($signedQuery->getQuery(), $url);

        static::assertArrayHasKey('shop-id', $url);
        static::assertArrayHasKey('shop-url', $url);
        static::assertArrayHasKey('timestamp', $url);
        static::assertArrayHasKey('sw-version', $url);
        static::assertArrayHasKey('in-app-purchases', $url);
        static::assertArrayHasKey('sw-context-language', $url);
        static::assertArrayHasKey('sw-user-language', $url);
        static::assertArrayHasKey('cicada-shop-signature', $url);

        static::assertSame('shopId', $url['shop-id']);
        static::assertSame('http://shop.url', $url['shop-url']);
        static::assertIsNumeric($url['timestamp']);
        static::assertSame('1.0.0', $url['sw-version']);
        static::assertSame('a6a4063ffda65516983ad40e8dc91db6', $url['in-app-purchases']);
        static::assertSame(Defaults::LANGUAGE_SYSTEM, $url['sw-context-language']);
        static::assertSame('en-GB', $url['sw-user-language']);
    }

    public function testThrowsWithoutAppSecret(): void
    {
        $app = new AppEntity();
        $app->setName('Foo');
        $app->setAppSecret(null);

        $querySigner = new QuerySigner(
            'http://shop.url',
            '1.0.0',
            $this->createMock(LocaleProvider::class),
            $this->createMock(ShopIdProvider::class),
            StaticInAppPurchaseFactory::createWithFeatures(),
        );

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('App secret is missing for app Foo');

        $querySigner->signUri('http://app.url/?foo=bar', $app, Context::createDefaultContext());
    }
}
