<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Filter;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\SnippetException;

#[Package('services-settings')]
class SnippetFilterFactory
{
    /**
     * @internal
     *
     * @param iterable<SnippetFilterInterface> $filters
     */
    public function __construct(private readonly iterable $filters)
    {
    }

    public function getFilter(string $name): SnippetFilterInterface
    {
        foreach ($this->filters as $filter) {
            if ($filter->supports($name)) {
                return $filter;
            }
        }

        throw SnippetException::filterNotFound($name, self::class);
    }
}
