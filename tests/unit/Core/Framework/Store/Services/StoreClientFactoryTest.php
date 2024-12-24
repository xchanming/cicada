<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Services;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\MiddlewareInterface;
use Cicada\Core\Framework\Store\Services\ShopSecretInvalidMiddleware;
use Cicada\Core\Framework\Store\Services\StoreClientFactory;
use Cicada\Core\Framework\Store\Services\StoreSessionExpiredMiddleware;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StoreClientFactory::class)]
class StoreClientFactoryTest extends TestCase
{
    public function testCreatesClientWithoutMiddlewares(): void
    {
        $expected = new Client($this->createConfig());

        $factory = new StoreClientFactory(new StaticSystemConfigService(['core.store.apiUri' => 'http://cicada.swag']));
        $client = $factory->create();

        static::assertEquals($expected, $client);
    }

    public function testCreatesClientWithMiddlewares(): void
    {
        $connection = $this->createMock(Connection::class);
        $middlewares = [
            new StoreSessionExpiredMiddleware($connection, new RequestStack()),
            new ShopSecretInvalidMiddleware($connection, new StaticSystemConfigService()),
        ];

        $expected = new Client($this->createConfig($middlewares));

        $factory = new StoreClientFactory(new StaticSystemConfigService(['core.store.apiUri' => 'http://cicada.swag']));
        $client = $factory->create($middlewares);

        static::assertEquals($expected, $client);
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     *
     * @return array{base_uri: string, headers: array<string, string>, handler: HandlerStack}
     */
    private function createConfig(array $middlewares = []): array
    {
        $handler = HandlerStack::create();
        foreach ($middlewares as $middleware) {
            $handler->push(Middleware::mapResponse($middleware));
        }

        return [
            'base_uri' => 'http://cicada.swag',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.api+json,application/json',
            ],
            'handler' => $handler,
        ];
    }
}
