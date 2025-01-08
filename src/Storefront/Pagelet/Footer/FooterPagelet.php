<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Footer;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Pagelet\NavigationPagelet;

/**
 * @codeCoverageIgnore
 */
#[Package('storefront')]
class FooterPagelet extends NavigationPagelet
{
    /**
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter serviceMenu will be required
     */
    public function __construct(
        ?Tree $navigation,
        protected ?CategoryCollection $serviceMenu = null,
    ) {
        parent::__construct($navigation);
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - Will only return CategoryCollection
     */
    public function getServiceMenu(): ?CategoryCollection
    {
        return $this->serviceMenu;
    }
}
