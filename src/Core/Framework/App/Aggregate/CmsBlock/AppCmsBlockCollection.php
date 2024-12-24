<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\CmsBlock;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @extends EntityCollection<AppCmsBlockEntity>
 */
#[Package('buyers-experience')]
class AppCmsBlockCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AppCmsBlockEntity::class;
    }
}
