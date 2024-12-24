<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Cicada\Core\Framework\Update\Services\ExtensionCompatibility;
use Cicada\Core\Framework\Update\Struct\Version;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 *
 * @phpstan-import-type Compatibility from ExtensionCompatibility
 */
#[Package('services-settings')]
class ExtensionCompatibilitiesResolvedEvent extends Event
{
    /**
     * @param list<Compatibility> $compatibilities
     */
    public function __construct(
        public Version $update,
        public ExtensionCollection $extensions,
        public array $compatibilities,
        public readonly Context $context
    ) {
    }
}
