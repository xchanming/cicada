<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Menu\Offcanvas;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Pagelet\NavigationPagelet;

#[Package('storefront')]
class MenuOffcanvasPagelet extends NavigationPagelet
{
    public function setNavigation(Tree $navigation): void
    {
        $this->navigation = $navigation;
    }
}
