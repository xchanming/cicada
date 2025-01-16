<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('framework')]
abstract class AbstractResolvedConfigLoader
{
    abstract public function getDecorated(): AbstractResolvedConfigLoader;

    abstract public function load(string $themeId, SalesChannelContext $context): array;
}
