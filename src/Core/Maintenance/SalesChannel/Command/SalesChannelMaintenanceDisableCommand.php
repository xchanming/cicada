<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\SalesChannel\Command;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal should be used over the CLI only
 */
#[AsCommand(
    name: 'sales-channel:maintenance:disable',
    description: 'Disable maintenance mode for a sales channel',
)]
#[Package('core')]
class SalesChannelMaintenanceDisableCommand extends SalesChannelMaintenanceEnableCommand
{
    protected bool $setMaintenanceMode = false;
}
