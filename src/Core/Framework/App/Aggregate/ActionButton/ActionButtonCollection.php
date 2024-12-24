<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\ActionButton;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 *
 * @extends EntityCollection<ActionButtonEntity>
 */
#[Package('core')]
class ActionButtonCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ActionButtonEntity::class;
    }
}
