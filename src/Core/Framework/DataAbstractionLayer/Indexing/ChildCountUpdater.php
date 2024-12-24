<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Indexing;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

#[Package('core')]
class ChildCountUpdater
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $registry,
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<string> $parentIds
     */
    public function update(string $entity, array $parentIds, Context $context): void
    {
        $definition = $this->registry->getByEntityName($entity);

        if (empty($parentIds)) {
            return;
        }

        RetryableTransaction::retryable($this->connection, function () use ($definition, $parentIds, $context): void {
            $this->trySingleUpdate($definition, $parentIds, $context);
        });
    }

    /**
     * @param array<string> $parentIds
     */
    private function trySingleUpdate(EntityDefinition $definition, array $parentIds, Context $context): void
    {
        $entity = $definition->getEntityName();
        $versionAware = $definition->isVersionAware();

        $sql = \sprintf(
            'UPDATE #entity#  as parent
                LEFT JOIN
                (
                    SELECT parent_id, count(id) total
                    FROM   #entity#
                    WHERE parent_id in (:ids)
                    %s
                    GROUP BY parent_id
                ) child ON parent.id = child.parent_id
            SET parent.child_count = IFNULL(child.total, 0)
            WHERE parent.id IN (:ids)
            %s',
            $versionAware ? 'AND version_id = :version' : '',
            $versionAware ? 'AND parent.version_id = :version' : ''
        );

        $sql = str_replace(
            ['#entity#'],
            [EntityDefinitionQueryHelper::escape($entity)],
            $sql
        );

        $params = ['ids' => Uuid::fromHexToBytesList($parentIds)];
        if ($versionAware) {
            $params['version'] = Uuid::fromHexToBytes($context->getVersionId());
        }

        $this->connection->executeStatement($sql, $params, ['ids' => ArrayParameterType::BINARY]);
    }
}
