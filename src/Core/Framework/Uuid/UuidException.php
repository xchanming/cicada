<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Uuid;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidLengthException;

#[Package('core')]
class UuidException extends HttpException
{
    public static function invalidUuid(string $uuid): CicadaHttpException
    {
        return new InvalidUuidException($uuid);
    }

    public static function invalidUuidLength(int $length, string $hex): CicadaHttpException
    {
        return new InvalidUuidLengthException($length, $hex);
    }
}
