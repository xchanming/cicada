<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation\Aggregate\SalutationTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SalutationTranslationEntity>
 */
#[Package('buyers-experience')]
class SalutationTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'salutation_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalutationTranslationEntity::class;
    }
}
