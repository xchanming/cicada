<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\System\Exception;

use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - Will be removed without replacement as it is not used anymore
 *
 * @phpstan-ignore cicada.internalClass
 */
#[Package('core')]
class ShopConfigurationException extends \RuntimeException
{
}
