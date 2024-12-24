<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class AppInstalledEvent extends ManifestChangedEvent
{
    final public const NAME = 'app.installed';

    public function getName(): string
    {
        return self::NAME;
    }
}
