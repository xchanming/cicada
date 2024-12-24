<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Language\Stubs;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageLoaderInterface;

/**
 * @internal
 *
 * @phpstan-import-type LanguageData from LanguageLoaderInterface
 */
#[Package('core')]
class StaticLanguageLoader implements LanguageLoaderInterface
{
    /**
     * @param LanguageData $languages
     */
    public function __construct(public readonly array $languages = [])
    {
    }

    /**
     * @return LanguageData
     */
    public function loadLanguages(): array
    {
        return $this->languages;
    }
}
