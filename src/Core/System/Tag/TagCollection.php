<?php declare(strict_types=1);

namespace Cicada\Core\System\Tag;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<TagEntity>
 */
#[Package('inventory')]
class TagCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'tag_collection';
    }

    protected function getExpectedClass(): string
    {
        return TagEntity::class;
    }
}
