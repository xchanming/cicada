<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\ShopSecretInvalidException;
use Cicada\Core\Framework\Store\Services\ShopSecretInvalidMiddleware;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
#[Package('checkout')]
class ShopSecretInvalidMiddlewareTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
    }

    public function testKeepsStoreTokensAndReturnsResponse(): void
    {
        $this->setAllUserStoreTokens('secret_token');
        $this->systemConfigService->set('core.store.shopSecret', 'shop-s3cr3t-token');

        $response = new Response(200, [], '{"payload":"data"}');

        $middleware = new ShopSecretInvalidMiddleware($this->connection, $this->systemConfigService);

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);

        foreach ($this->fetchAllUserStoreTokens() as $token) {
            static::assertSame('secret_token', $token['store_token']);
        }

        static::assertSame('shop-s3cr3t-token', $this->systemConfigService->get('core.store.shopSecret'));
    }

    public function testKeepsStoreTokensAndReturnsResponseWithRewoundBody(): void
    {
        $this->setAllUserStoreTokens('secret_token');
        $this->systemConfigService->set('core.store.shopSecret', 'shop-s3cr3t-token');

        $response = new Response(401, [], '{"payload":"data"}');

        $middleware = new ShopSecretInvalidMiddleware($this->connection, $this->systemConfigService);

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);

        foreach ($this->fetchAllUserStoreTokens() as $token) {
            static::assertSame('secret_token', $token['store_token']);
        }

        static::assertSame('shop-s3cr3t-token', $this->systemConfigService->get('core.store.shopSecret'));
    }

    public function testThrowsAndDeletesStoreTokensIfApiRespondsWithTokenExpiredException(): void
    {
        $this->setAllUserStoreTokens('secret_token');

        $response = new Response(401, [], '{"code":"CicadaPlatformException-68"}');

        $middleware = new ShopSecretInvalidMiddleware($this->connection, $this->systemConfigService);

        $this->expectException(ShopSecretInvalidException::class);
        $middleware($response);

        foreach ($this->fetchAllUserStoreTokens() as $token) {
            static::assertNull($token['store_token']);
        }

        static::assertNull($this->systemConfigService->get('core.store.shopSecret'));
    }

    private function setAllUserStoreTokens(string $storeToken): void
    {
        $this->connection->executeStatement('UPDATE user SET store_token = :storeToken', ['storeToken' => $storeToken]);
    }

    /**
     * @return array<int, array<string|null>>
     */
    private function fetchAllUserStoreTokens(): array
    {
        return $this->connection->executeQuery('SELECT store_token FROM user')->fetchAllAssociative();
    }
}
