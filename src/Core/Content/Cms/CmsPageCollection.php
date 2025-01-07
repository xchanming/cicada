<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CmsPageEntity>
 */
#[Package('discovery')]
class CmsPageCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'cms_page_collection';
    }

    protected function getExpectedClass(): string
    {
        return CmsPageEntity::class;
    }
}
