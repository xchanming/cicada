<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Language\Stubs;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\SalesChannelLanguageLoader;

/**
 * @internal
 */
#[Package('core')]
class StaticSalesChannelLanguageLoader extends SalesChannelLanguageLoader
{
    /**
     * @param array<string, array<string>> $languages
     */
    public function __construct(private readonly array $languages = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function loadLanguages(): array
    {
        return $this->languages;
    }
}
