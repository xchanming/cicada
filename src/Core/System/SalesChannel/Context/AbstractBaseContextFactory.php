<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\BaseContext;

/**
 * @internal
 */
#[Package('core')]
abstract class AbstractBaseContextFactory
{
    /**
     * @param array<string, mixed> $options
     */
    abstract public function create(string $salesChannelId, array $options = []): BaseContext;
}
