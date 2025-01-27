<?php

declare(strict_types=1);

namespace Cicada\Storefront\Framework\Twig;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 *
 * @internal
 */
#[Package('framework')]
final readonly class NavigationInfo
{
    /**
     * @param list<string> $pathIdList
     */
    public function __construct(
        public string $id,
        public array $pathIdList,
    ) {
    }
}
