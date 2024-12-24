<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<ThemeSalesChannel>
 */
#[Package('storefront')]
class ThemeSalesChannelCollection extends Collection
{
    /**
     * @var ThemeSalesChannel[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $elements = [];

    protected function getExpectedClass(): string
    {
        return ThemeSalesChannel::class;
    }
}
