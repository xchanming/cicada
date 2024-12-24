<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Framework\Twig\TokenParser\IconTokenParser;
use Twig\Extension\AbstractExtension;

#[Package('storefront')]
class IconExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct()
    {
    }

    public function getTokenParsers(): array
    {
        return [
            new IconTokenParser(),
        ];
    }
}
