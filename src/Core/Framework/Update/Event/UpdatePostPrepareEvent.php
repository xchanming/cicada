<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class UpdatePostPrepareEvent extends UpdateEvent
{
    public function __construct(
        Context $context,
        private readonly string $currentVersion,
        private readonly string $newVersion
    ) {
        parent::__construct($context);
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }
}
