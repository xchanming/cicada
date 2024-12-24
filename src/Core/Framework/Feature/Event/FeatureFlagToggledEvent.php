<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Feature\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class FeatureFlagToggledEvent extends Event
{
    public function __construct(
        public readonly string $feature,
        public readonly bool $active
    ) {
    }
}
