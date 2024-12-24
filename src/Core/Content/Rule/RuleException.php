<?php

declare(strict_types=1);

namespace Cicada\Core\Content\Rule;

use Cicada\Core\Framework\DataAbstractionLayer\Exception\UnsupportedCommandTypeException;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class RuleException extends HttpException
{
    public static function unsupportedCommandType(WriteCommand $command): HttpException
    {
        return new UnsupportedCommandTypeException($command);
    }
}
