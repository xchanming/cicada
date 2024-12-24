<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Hreflang;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\StructCollection;

/**
 * @extends StructCollection<HreflangStruct>
 */
#[Package('buyers-experience')]
class HreflangCollection extends StructCollection
{
    public function getApiAlias(): string
    {
        return 'seo_hreflang_collection';
    }

    protected function getExpectedClass(): string
    {
        return HreflangStruct::class;
    }
}
