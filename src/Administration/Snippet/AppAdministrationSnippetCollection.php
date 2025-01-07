<?php declare(strict_types=1);

namespace Cicada\Administration\Snippet;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<AppAdministrationSnippetEntity>
 *
 * @method void add(AppAdministrationSnippetEntity $entity)
 * @method void set(string $key, AppAdministrationSnippetEntity $entity)
 * @method AppAdministrationSnippetEntity[] getIterator()
 * @method AppAdministrationSnippetEntity[] getElements()
 * @method AppAdministrationSnippetEntity|null get(string $key)
 * @method AppAdministrationSnippetEntity|null first()
 * @method AppAdministrationSnippetEntity|null last()
 */
#[Package('discovery')]
class AppAdministrationSnippetCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'administration_snippet_collection';
    }

    protected function getExpectedClass(): string
    {
        return AppAdministrationSnippetEntity::class;
    }
}
