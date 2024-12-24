<?php
declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PromotionTranslationEntity>
 */
#[Package('buyers-experience')]
class PromotionTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'promotion_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return PromotionTranslationEntity::class;
    }
}
