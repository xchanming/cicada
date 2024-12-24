<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\Gateways;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\Gateway\CheckoutGateway;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[CoversClass(CheckoutGateway::class)]
#[Package('checkout')]
class CheckoutGatewayTest extends TestCase
{
    public function testParse(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/testGateway/manifest.xml');

        static::assertNotNull($manifest->getGateways());

        $gateways = $manifest->getGateways();

        static::assertNotNull($gateways->getCheckout());

        $checkoutGateway = $gateways->getCheckout();
        static::assertSame('https://foo.bar/example/checkout', $checkoutGateway->getUrl());
    }
}
