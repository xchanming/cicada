<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class AppUpdatedEvent extends ManifestChangedEvent
{
    final public const NAME = 'app.updated';

    public function getName(): string
    {
        return self::NAME;
    }
}
