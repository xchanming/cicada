<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\Gateways;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\Gateway\InAppPurchasesGateway;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InAppPurchasesGateway::class)]
#[Package('checkout')]
class InAppPurchasesGatewayTest extends TestCase
{
    public function testParse(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/testGateway/manifest.xml');

        static::assertNotNull($manifest->getGateways());

        $gateways = $manifest->getGateways();

        static::assertNotNull($gateways->getInAppPurchasesGateway());

        $inAppPurchasesFilter = $gateways->getInAppPurchasesGateway();
        static::assertSame('https://foo.bar/example/iap-filter', $inAppPurchasesFilter->getUrl());
    }
}
