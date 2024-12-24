<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\FlowActionTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<AppFlowActionTranslationEntity>
 */
#[Package('core')]
class AppFlowActionTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AppFlowActionTranslationEntity::class;
    }
}
