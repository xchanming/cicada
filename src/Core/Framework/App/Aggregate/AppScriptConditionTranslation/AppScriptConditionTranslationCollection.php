<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\AppScriptConditionTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<AppScriptConditionTranslationEntity>
 */
#[Package('core')]
class AppScriptConditionTranslationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'app_script_condition_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return AppScriptConditionTranslationEntity::class;
    }
}
