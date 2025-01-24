<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\Environment;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
interface EnvironmentHelperTransformerInterface
{
    public static function transform(EnvironmentHelperTransformerData $data): void;
}
