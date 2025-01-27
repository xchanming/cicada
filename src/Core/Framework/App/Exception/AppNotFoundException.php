<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('framework')]
class AppNotFoundException extends AppException
{
}
