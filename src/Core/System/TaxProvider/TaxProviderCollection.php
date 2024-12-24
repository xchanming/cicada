<?php declare(strict_types=1);

namespace Cicada\Core\System\TaxProvider;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<TaxProviderEntity>
 */
#[Package('checkout')]
class TaxProviderCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'tax_provider_collection';
    }

    public function sortByPriority(): void
    {
        $this->sort(fn (TaxProviderEntity $a, TaxProviderEntity $b) => $b->getPriority() <=> $a->getPriority());
    }

    protected function getExpectedClass(): string
    {
        return TaxProviderEntity::class;
    }
}
