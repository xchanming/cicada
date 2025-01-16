<?php

declare(strict_types=1);

namespace Cicada\Storefront\Framework\Twig;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('framework')]
final readonly class NavigationInfo
{
    public function __construct(
        public string $id,
        public string $path,
    ) {
    }
}
