<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractSystemConfigLoader
{
    abstract public function getDecorated(): AbstractSystemConfigLoader;

    abstract public function load(?string $salesChannelId): array;
}
