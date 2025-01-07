<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataResolver\Element;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;

/**
 * @implements \IteratorAggregate<array-key, EntitySearchResult<EntityCollection>>
 */
#[Package('discovery')]
class ElementDataCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, EntitySearchResult<EntityCollection>>
     */
    protected array $searchResults = [];

    /**
     * @param EntitySearchResult<EntityCollection> $entitySearchResult
     */
    public function add(string $key, EntitySearchResult $entitySearchResult): void
    {
        $this->searchResults[$key] = $entitySearchResult;
    }

    /**
     * @return EntitySearchResult<EntityCollection>|null
     */
    public function get(string $key): ?EntitySearchResult
    {
        return $this->searchResults[$key] ?? null;
    }

    /**
     * @return \Traversable<string, EntitySearchResult<EntityCollection>>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->searchResults;
    }

    public function count(): int
    {
        return \count($this->searchResults);
    }
}
