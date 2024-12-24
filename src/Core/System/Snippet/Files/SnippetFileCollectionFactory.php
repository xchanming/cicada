<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Files;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class SnippetFileCollectionFactory
{
    /**
     * @internal
     */
    public function __construct(private readonly SnippetFileLoaderInterface $snippetFileLoader)
    {
    }

    public function createSnippetFileCollection(): SnippetFileCollection
    {
        $collection = new SnippetFileCollection();
        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        return $collection;
    }
}
