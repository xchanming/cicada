<?php declare(strict_types=1);

namespace Cicada\Core\Framework\SystemCheck\Check;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('core')]
enum Status
{
    case OK;
    case UNKNOWN;

    case SKIPPED;

    case WARNING;

    case ERROR;

    case FAILURE;
}
