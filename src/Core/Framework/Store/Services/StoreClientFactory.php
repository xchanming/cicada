<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * @internal
 */
#[Package('checkout')]
class StoreClientFactory
{
    private const CONFIG_KEY_STORE_API_URI = 'core.store.apiUri';

    public function __construct(
        private readonly SystemConfigService $configService
    ) {
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function create(iterable $middlewares = []): ClientInterface
    {
        $stack = HandlerStack::create();

        foreach ($middlewares as $middleware) {
            $stack->push(Middleware::mapResponse($middleware));
        }

        $config = $this->getClientBaseConfig();
        $config['handler'] = $stack;

        return new Client($config);
    }

    /**
     * @return array{base_uri: string, headers: array<string, string>}
     */
    private function getClientBaseConfig(): array
    {
        return [
            'base_uri' => $this->configService->getString(self::CONFIG_KEY_STORE_API_URI),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.api+json,application/json',
            ],
        ];
    }
}
