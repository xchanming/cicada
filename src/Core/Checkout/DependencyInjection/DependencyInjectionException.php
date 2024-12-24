<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\DependencyInjection;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class DependencyInjectionException extends HttpException
{
    public const CART_REDIS_NOT_CONFIGURED = 'CHECKOUT__CART_REDIS_NOT_CONFIGURED';

    public static function redisNotConfiguredForCartStorage(): self
    {
        return new self(
            500,
            self::CART_REDIS_NOT_CONFIGURED,
            // @deprecated tag:v6.7.0 - remove '"cicada.number_range.config.dsn or"' from this message - only "cicada.cart.storage.config.connection" would be supported
            'Parameter "cicada.cart.storage.config.dsn" or "cicada.cart.storage.config.connection" is required for redis storage'
        );
    }
}
