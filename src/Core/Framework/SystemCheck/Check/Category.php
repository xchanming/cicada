<?php declare(strict_types=1);

namespace Cicada\Core\Framework\SystemCheck\Check;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('core')]
enum Category: int
{
    case SYSTEM = 0;

    case FEATURE = 8;

    case EXTERNAL = 32;

    case AUXILIARY = 128;
}
