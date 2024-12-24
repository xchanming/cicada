<?php declare(strict_types=1);

namespace Cicada\Core\System\Language;

use Cicada\Core\Framework\Log\Package;

/**
 * @phpstan-type LanguageData array<string, array{id: string, code: string, parentId: string, parentCode?: ?string}>
 */
#[Package('core')]
interface LanguageLoaderInterface
{
    /**
     * @return LanguageData
     */
    public function loadLanguages(): array;
}
