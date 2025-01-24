<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;

/**
 * @internal
 */
#[Package('framework')]
class StaticAnalyzeKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return \sprintf(
            '%s/var/cache/static_%s',
            $this->getProjectDir(),
            $this->getEnvironment(),
        );
    }
}
