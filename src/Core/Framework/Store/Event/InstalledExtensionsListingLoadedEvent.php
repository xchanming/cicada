<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[Package('checkout')]
class InstalledExtensionsListingLoadedEvent extends Event
{
    public function __construct(public ExtensionCollection $extensionCollection, public readonly Context $context)
    {
    }
}
