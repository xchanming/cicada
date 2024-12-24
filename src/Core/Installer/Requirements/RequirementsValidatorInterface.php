<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Requirements;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Installer\Requirements\Struct\RequirementsCheckCollection;

/**
 * @internal
 */
#[Package('core')]
interface RequirementsValidatorInterface
{
    public function validateRequirements(RequirementsCheckCollection $checks): RequirementsCheckCollection;
}
