<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
abstract class AbstractSalesChannelContextFactory
{
    abstract public function getDecorated(): AbstractSalesChannelContextFactory;

    /**
     * @param array<string, mixed> $options
     */
    abstract public function create(string $token, string $salesChannelId, array $options = []): SalesChannelContext;
}
