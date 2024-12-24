<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\UsageData\UsageDataException;

/**
 * @internal
 */
#[Package('data-services')]
class ConsentAlreadyRequestedException extends UsageDataException
{
}
