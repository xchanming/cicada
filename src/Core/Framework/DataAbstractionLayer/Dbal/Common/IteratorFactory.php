<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;

/**
 * @final
 */
#[Package('framework')]
class IteratorFactory
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly DefinitionInstanceRegistry $registry
    ) {
    }

    /**
     * @param array{offset: int|null}|null $lastId
     */
    public function createIterator(string|EntityDefinition $definition, ?array $lastId = null, int $limit = 50): IterableQuery
    {
        if (\is_string($definition)) {
            $definition = $this->registry->getByEntityName($definition);
        }

        $entity = $definition->getEntityName();

        $escaped = EntityDefinitionQueryHelper::escape($entity);
        $query = $this->connection->createQueryBuilder();
        $query->from($escaped);
        $query->setMaxResults($limit);

        if ($definition->hasAutoIncrement()) {
            $query->select($escaped . '.auto_increment', 'LOWER(HEX(' . $escaped . '.id)) as id');
            $query->andWhere($escaped . '.auto_increment > :lastId');
            $query->addOrderBy($escaped . '.auto_increment');
            $query->setParameter('lastId', 0);

            if ($lastId !== null) {
                $query->setParameter('lastId', $lastId['offset']);
            }

            return new LastIdQuery($query);
        }

        $query->select($escaped . '.id', 'LOWER(HEX(' . $escaped . '.id))');
        $query->setFirstResult(0);
        if ($lastId !== null) {
            $query->setFirstResult((int) $lastId['offset']);
        }

        return new OffsetQuery($query);
    }
}
