<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Menu\Offcanvas;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Pagelet\NavigationPagelet;

#[Package('framework')]
class MenuOffcanvasPagelet extends NavigationPagelet
{
    /**
     * @deprecated tag:v6.7.0 - Will be removed, as it is unused
     */
    public function setNavigation(Tree $navigation): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __FUNCTION__, 'v6.7.0.0')
        );
        $this->navigation = $navigation;
    }
}
