<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\StoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Exception\ShopSecretInvalidException;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ShopSecretInvalidMiddleware implements MiddlewareInterface
{
    private const INVALID_SHOP_SECRET = 'CicadaPlatformException-68';

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        if ($response->getStatusCode() !== 401) {
            return $response;
        }

        $body = json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
        $code = $body['code'] ?? null;

        if ($code !== self::INVALID_SHOP_SECRET) {
            $response->getBody()->rewind();

            return $response;
        }

        $this->connection->executeStatement('UPDATE user SET store_token = NULL');

        $this->systemConfigService->delete(StoreRequestOptionsProvider::CONFIG_KEY_STORE_SHOP_SECRET);

        throw new ShopSecretInvalidException();
    }
}
