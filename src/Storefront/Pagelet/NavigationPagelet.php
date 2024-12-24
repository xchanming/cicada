<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Log\Package;

#[Package('storefront')]
abstract class NavigationPagelet extends Pagelet
{
    /**
     * @var Tree|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigation;

    public function __construct(?Tree $navigation)
    {
        $this->navigation = $navigation;
    }

    public function getNavigation(): ?Tree
    {
        return $this->navigation;
    }
}
