<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook\EventLog;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<WebhookEventLogEntity>
 */
#[Package('core')]
class WebhookEventLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WebhookEventLogEntity::class;
    }
}
