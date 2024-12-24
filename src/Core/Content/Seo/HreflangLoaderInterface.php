<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Cicada\Core\Content\Seo\Hreflang\HreflangCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
interface HreflangLoaderInterface
{
    public function load(HreflangLoaderParameter $parameter): HreflangCollection;
}
