<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\Client;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\UsageData\Client\GatewayClient;
use Cicada\Core\System\UsageData\Services\ShopIdProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[Package('data-services')]
#[CoversClass(GatewayClient::class)]
class GatewayClientTest extends TestCase
{
    public function testGatewayAllowsPush(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $gatewayKillSwitchOff = json_encode(['killswitch' => false], \JSON_THROW_ON_ERROR);

            return new MockResponse($gatewayKillSwitchOff);
        });

        $gatewayClient = new GatewayClient(
            $client,
            $this->createMock(ShopIdProvider::class),
            true
        );

        static::assertTrue($gatewayClient->isGatewayAllowsPush());
    }

    public function testGatewayDoesNotAllowPush(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $gatewayKillSwitchOn = json_encode(['killswitch' => true], \JSON_THROW_ON_ERROR);

            return new MockResponse($gatewayKillSwitchOn);
        });

        $gatewayClient = new GatewayClient(
            $client,
            $this->createMock(ShopIdProvider::class),
            true
        );

        static::assertFalse($gatewayClient->isGatewayAllowsPush());
    }

    public function testGatewayDoesNotAllowPushInDevEnvironment(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $gatewayKillSwitchOn = json_encode(['killswitch' => false], \JSON_THROW_ON_ERROR);

            return new MockResponse($gatewayKillSwitchOn);
        });

        $gatewayClient = new GatewayClient(
            $client,
            $this->createMock(ShopIdProvider::class),
            false
        );

        static::assertFalse($gatewayClient->isGatewayAllowsPush());
    }
}
