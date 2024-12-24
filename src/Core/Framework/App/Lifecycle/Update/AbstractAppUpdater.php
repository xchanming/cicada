<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Update;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
abstract class AbstractAppUpdater
{
    abstract public function updateApps(Context $context): void;

    abstract protected function getDecorated(): AbstractAppUpdater;
}
