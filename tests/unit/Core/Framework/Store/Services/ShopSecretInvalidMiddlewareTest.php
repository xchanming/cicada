<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\StoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Exception\ShopSecretInvalidException;
use Cicada\Core\Framework\Store\Services\ShopSecretInvalidMiddleware;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShopSecretInvalidMiddleware::class)]
class ShopSecretInvalidMiddlewareTest extends TestCase
{
    public function testKeepsStoreTokensAndReturnsResponse(): void
    {
        $response = new Response(200, [], '{"payload":"data"}');

        $middleware = new ShopSecretInvalidMiddleware(
            $this->createMock(Connection::class),
            $this->createMock(SystemConfigService::class)
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    public function testKeepsStoreTokensAndReturnsResponseWithRewoundBody(): void
    {
        $response = new Response(401, [], '{"payload":"data"}');

        $middleware = new ShopSecretInvalidMiddleware(
            $this->createMock(Connection::class),
            $this->createMock(SystemConfigService::class)
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    public function testThrowsAndDeletesStoreTokensIfApiRespondsWithTokenExpiredException(): void
    {
        $response = new Response(401, [], '{"code":"CicadaPlatformException-68"}');

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('executeStatement');

        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('delete')
            ->with(StoreRequestOptionsProvider::CONFIG_KEY_STORE_SHOP_SECRET);

        $middleware = new ShopSecretInvalidMiddleware(
            $connection,
            $systemConfigService
        );

        $this->expectException(ShopSecretInvalidException::class);
        $middleware($response);
    }
}
