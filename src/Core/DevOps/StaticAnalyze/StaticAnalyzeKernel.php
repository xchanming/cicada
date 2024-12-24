<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;

/**
 * @internal
 */
#[Package('core')]
class StaticAnalyzeKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return \sprintf(
            '%s/var/cache/%s',
            $this->getProjectDir(),
            $this->getEnvironment()
        );
    }
}
