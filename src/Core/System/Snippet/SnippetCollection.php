<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SnippetEntity>
 */
#[Package('core')]
class SnippetCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'snippet_collection';
    }

    protected function getExpectedClass(): string
    {
        return SnippetEntity::class;
    }
}
