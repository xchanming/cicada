<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Cookie;

use Cicada\Core\Framework\Log\Package;

#[Package('storefront')]
interface CookieProviderInterface
{
    /**
     * @return array<string|int, mixed>
     */
    public function getCookieGroups(): array;
}
