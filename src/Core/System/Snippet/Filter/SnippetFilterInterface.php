<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Filter;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\SnippetService;

/**
 * @phpstan-import-type SnippetArray from SnippetService
 */
#[Package('services-settings')]
interface SnippetFilterInterface
{
    public function getName(): string;

    public function supports(string $name): bool;

    /**
     * @param SnippetArray $snippets
     * @param true|string|list<string> $requestFilterValue
     *
     * @return SnippetArray
     */
    public function filter(array $snippets, $requestFilterValue): array;
}
