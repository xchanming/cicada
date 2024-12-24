<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Payload\Struct;

use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Source::class)]
class SourceTest extends TestCase
{
    public function testPayload(): void
    {
        $url = 'https://foo.bar';
        $shopId = 'foo';
        $appVersion = '1.0.0';
        $inAppPurchases = 'jwt-1';

        $source = new Source($url, $shopId, $appVersion, $inAppPurchases);

        static::assertSame($url, $source->getUrl());
        static::assertSame($shopId, $source->getShopId());
        static::assertSame($appVersion, $source->getAppVersion());
        static::assertSame($inAppPurchases, $source->getInAppPurchases());
    }
}
