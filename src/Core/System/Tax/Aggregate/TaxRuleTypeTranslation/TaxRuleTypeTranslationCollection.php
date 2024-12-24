<?php declare(strict_types=1);

namespace Cicada\Core\System\Tax\Aggregate\TaxRuleTypeTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<TaxRuleTypeTranslationEntity>
 */
#[Package('checkout')]
class TaxRuleTypeTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'tax_rule_type_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return TaxRuleTypeTranslationEntity::class;
    }
}
