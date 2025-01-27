<?php declare(strict_types=1);

namespace Cicada\Administration\Snippet;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface SnippetFinderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function findSnippets(string $locale): array;
}
