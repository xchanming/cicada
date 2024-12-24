<?php declare(strict_types=1);

namespace Cicada\Core\Content\Test\Category\Service;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

/**
 * @internal
 */
class CountingEntitySearcher implements EntitySearcherInterface
{
    /**
     * @var int[]
     */
    private static array $count = [];

    public function __construct(private readonly EntitySearcherInterface $inner)
    {
    }

    public function search(EntityDefinition $definition, Criteria $criteria, Context $context): IdSearchResult
    {
        static::$count[$definition->getEntityName()] ??= 0 + 1;

        return $this->inner->search($definition, $criteria, $context);
    }

    public static function resetCount(): void
    {
        static::$count = [];
    }

    public static function getSearchOperationCount(string $entityName): int
    {
        return static::$count[$entityName] ?? 0;
    }
}
