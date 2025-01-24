<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Staging\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[Package('framework')]
class SetupStagingEvent
{
    public const CONFIG_FLAG = 'core.staging';

    public bool $canceled = false;

    public function __construct(
        public readonly Context $context,
        public readonly SymfonyStyle $io,
    ) {
    }
}
