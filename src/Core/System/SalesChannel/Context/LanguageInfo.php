<?php

declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('core')]
final readonly class LanguageInfo
{
    public function __construct(
        public string $name,
        public string $localeCode,
    ) {
    }
}
