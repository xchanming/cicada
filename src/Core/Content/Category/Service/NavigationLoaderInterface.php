<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Service;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('discovery')]
interface NavigationLoaderInterface
{
    /**
     * Returns the first two levels of the category tree, as well as all parents of the active category
     * and the active categories first level of children.
     * The provided active id will be marked as selected
     */
    public function load(string $activeId, SalesChannelContext $context, string $rootId, int $depth = 2): Tree;
}
