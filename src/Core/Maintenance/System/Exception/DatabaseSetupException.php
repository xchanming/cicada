<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\System\Exception;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\MaintenanceException;

if (!Feature::isActive('v6.7.0.0')) {
    /**
     * @deprecated tag:v6.7.0 - reason:becomes-internal
     */
    #[Package('core')]
    class DatabaseSetupException extends \RuntimeException
    {
    }
} else {
    /**
     * @internal
     */
    #[Package('core')]
    class DatabaseSetupException extends MaintenanceException
    {
    }
}
