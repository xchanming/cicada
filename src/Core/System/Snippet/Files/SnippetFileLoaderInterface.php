<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Files;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface SnippetFileLoaderInterface
{
    public function loadSnippetFilesIntoCollection(SnippetFileCollection $snippetFileCollection): void;
}
