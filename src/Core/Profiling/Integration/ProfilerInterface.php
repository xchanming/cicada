<?php declare(strict_types=1);

namespace Cicada\Core\Profiling\Integration;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal experimental atm
 */
#[Package('core')]
interface ProfilerInterface
{
    public function start(string $title, string $category, array $tags): void;

    public function stop(string $title): void;
}
