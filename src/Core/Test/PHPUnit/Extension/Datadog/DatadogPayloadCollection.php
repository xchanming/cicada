<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\Datadog;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @internal
 *
 * @extends Collection<DatadogPayload>
 */
#[Package('core')]
class DatadogPayloadCollection extends Collection
{
}
