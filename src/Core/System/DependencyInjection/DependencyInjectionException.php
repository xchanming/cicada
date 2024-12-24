<?php declare(strict_types=1);

namespace Cicada\Core\System\DependencyInjection;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class DependencyInjectionException extends HttpException
{
    public const NUMBER_RANGE_REDIS_NOT_CONFIGURED = 'SYSTEM__NUMBER_RANGE_REDIS_NOT_CONFIGURED';

    public static function redisNotConfiguredForNumberRangeIncrementer(): self
    {
        return new self(
            500,
            self::NUMBER_RANGE_REDIS_NOT_CONFIGURED,
            // @deprecated tag:v6.7.0 - remove '"cicada.number_range.config.dsn" or' from this message - only "cicada.number_range.config.connection" would be supported
            'Parameter "cicada.number_range.config.dsn" or "cicada.number_range.config.connection" is required for redis storage'
        );
    }
}
