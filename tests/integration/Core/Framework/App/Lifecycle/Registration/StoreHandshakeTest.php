<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Lifecycle\Registration;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Lifecycle\Registration\StoreHandshake;
use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Kernel;

/**
 * @internal
 */
class StoreHandshakeTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    public function testUrlContainsAllNecessaryElements(): void
    {
        $shopUrl = 'test.shop.com';
        $appEndpoint = 'https://test.com/install';
        $shopId = Random::getAlphanumericString(12);

        $storeClientMock = $this->createMock(StoreClient::class);
        $storeClientMock->expects(static::once())
            ->method('signPayloadWithAppSecret')
            ->willReturn('1234');

        $handshake = new StoreHandshake($shopUrl, $appEndpoint, '', $shopId, $storeClientMock, Kernel::CICADA_FALLBACK_VERSION);

        $request = $handshake->assembleRequest();
        static::assertStringStartsWith($appEndpoint, (string) $request->getUri());

        $queryParams = [];
        parse_str($request->getUri()->getQuery(), $queryParams);

        static::assertArrayHasKey('shop-url', $queryParams);
        static::assertSame(urlencode($shopUrl), $queryParams['shop-url']);

        static::assertArrayHasKey('shop-id', $queryParams);
        static::assertSame($shopId, $queryParams['shop-id']);

        static::assertArrayHasKey('timestamp', $queryParams);
        static::assertIsString($queryParams['timestamp']);
        static::assertNotEmpty($queryParams['timestamp']);

        static::assertTrue($request->hasHeader('cicada-app-signature'));
        static::assertSame(
            '1234',
            $request->getHeaderLine('cicada-app-signature')
        );

        static::assertNotEmpty($request->getHeaderLine('sw-version'));
    }

    public function testAppProof(): void
    {
        $shopUrl = 'test.shop.com';
        $appEndpoint = 'https://test.com/install';
        $appName = 'testapp';
        $shopId = Random::getAlphanumericString(12);

        $storeClientMock = $this->createMock(StoreClient::class);
        $storeClientMock->expects(static::once())
            ->method('signPayloadWithAppSecret')
            ->with($shopId . $shopUrl . $appName, $appName)
            ->willReturn('1234');

        $handshake = new StoreHandshake($shopUrl, $appEndpoint, $appName, $shopId, $storeClientMock, Kernel::CICADA_FALLBACK_VERSION);

        static::assertSame('1234', $handshake->fetchAppProof());
    }

    public function testThrowsIfSbpRespondsWithUnauthorized(): void
    {
        $storeClient = $this->createMock(StoreClient::class);
        $json = \json_encode(['code' => 'CicadaPlatformException-1']);

        static::assertNotFalse($json);

        $storeClient->method('signPayloadWithAppSecret')
            ->willThrowException(new ClientException(
                '',
                new Request('POST', 'app_generate_signature'),
                new Response(401, [], $json)
            ));

        $handshake = new StoreHandshake(
            'http://shop.url',
            'http://app.url',
            'TestApp',
            'my-shop-id',
            $storeClient,
            Kernel::CICADA_FALLBACK_VERSION
        );

        static::expectException(AppException::class);
        static::expectExceptionMessage('License for app "TestApp" could not be verified');

        $handshake->assembleRequest();
    }
}
