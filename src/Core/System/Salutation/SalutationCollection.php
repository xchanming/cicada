<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SalutationEntity>
 */
#[Package('core')]
class SalutationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'salutation_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalutationEntity::class;
    }
}
