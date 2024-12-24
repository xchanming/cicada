<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\ActionButtonTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @extends EntityCollection<ActionButtonTranslationEntity>
 */
#[Package('core')]
class ActionButtonTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ActionButtonTranslationEntity::class;
    }
}
