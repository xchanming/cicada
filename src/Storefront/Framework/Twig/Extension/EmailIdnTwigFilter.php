<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Twig\Extension;

use Cicada\Core\Framework\Log\Package;
use Twig\Extension\AbstractExtension;

/**
 * @deprecated tag:v6.7.0 - unused, is moved to core, reason:remove-subscriber
 */
#[Package('checkout')]
class EmailIdnTwigFilter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [];
    }
}
