<?php declare(strict_types=1);

namespace Cicada\Core\System\TaxProvider\Aggregate\TaxProviderTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<TaxProviderTranslationEntity>
 */
#[Package('checkout')]
class TaxProviderTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'tax_provider_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return TaxProviderTranslationEntity::class;
    }
}
