<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class UpdatePreFinishEvent extends UpdateEvent
{
    public function __construct(
        Context $context,
        private readonly string $oldVersion,
        private readonly string $newVersion
    ) {
        parent::__construct($context);
    }

    public function getOldVersion(): string
    {
        return $this->oldVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }
}
