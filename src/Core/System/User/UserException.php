<?php declare(strict_types=1);

namespace Cicada\Core\System\User;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class UserException extends HttpException
{
    final public const SALES_CHANNEL_NOT_FOUND = 'USER__SALES_CHANNEL_NOT_FOUND';

    public static function salesChannelNotFound(): HttpException
    {
        return new self(
            Response::HTTP_PRECONDITION_FAILED,
            self::SALES_CHANNEL_NOT_FOUND,
            'No sales channel found.',
        );
    }
}
