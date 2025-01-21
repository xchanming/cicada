<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Schema;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\ApiDefinition\Generator\CachedEntitySchemaGenerator;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @internal
 */
#[Package('framework')]
class CustomEntityPersister
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AdapterInterface $cache
    ) {
    }

    /**
     * @param array<string, array<string, mixed>> $customEntities
     */
    public function update(
        array $customEntities,
        ?string $extensionEntityType = null,
        ?string $extensionId = null
    ): void {
        $names = array_column($customEntities, 'name');

        $existings = $this->connection->fetchAllAssociativeIndexed(
            'SELECT `name`, LOWER(HEX(id)) as id, created_at FROM custom_entity WHERE `name` IN (:names)',
            ['names' => $names],
            ['names' => ArrayParameterType::STRING]
        );

        if ($extensionEntityType === PluginEntity::class && $extensionId) {
            $this->connection->executeStatement(
                'DELETE FROM custom_entity WHERE plugin_id = :id',
                ['id' => Uuid::fromHexToBytes($extensionId)]
            );
        } elseif ($extensionEntityType === AppEntity::class && $extensionId) {
            $this->connection->executeStatement(
                'DELETE FROM custom_entity WHERE app_id = :id',
                ['id' => Uuid::fromHexToBytes($extensionId)]
            );
        } else {
            // custom entity without any app or plugin id --> created by the user and not by any extension
            $this->connection->executeStatement('DELETE FROM custom_entity WHERE app_id IS NULL AND plugin_id IS NULL');
        }

        $inserts = new MultiInsertQueryQueue($this->connection, 25, false, true);
        foreach ($customEntities as $customEntity) {
            unset($customEntity['cmsAware']);

            $customEntity['plugin_id'] = $extensionEntityType === PluginEntity::class && $extensionId ? Uuid::fromHexToBytes($extensionId) : null;
            $customEntity['app_id'] = $extensionEntityType === AppEntity::class && $extensionId ? Uuid::fromHexToBytes($extensionId) : null;

            $customEntity['flags'] = json_encode($customEntity['flags'], \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);
            $customEntity['fields'] = json_encode($customEntity['fields'], \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);

            $customEntity['custom_fields_aware'] = ($customEntity['customFieldsAware'] ?? false) ? 1 : 0;
            $customEntity['label_property'] = $customEntity['labelProperty'] ?? null;
            unset($customEntity['customFieldsAware']);
            unset($customEntity['labelProperty']);

            $name = $customEntity['name'];
            $id = isset($existings[$name]) ? $existings[$name]['id'] : Uuid::randomHex();
            $customEntity['id'] = Uuid::fromHexToBytes($id);

            $customEntity['created_at'] = isset($existings[$name]) ? $existings[$name]['created_at'] : (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            $customEntity['updated_at'] = isset($existings[$name]) ? (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT) : null;

            $inserts->addInsert('custom_entity', $customEntity);
        }

        $inserts->execute();

        $this->cache->deleteItem(CachedEntitySchemaGenerator::CACHE_KEY);
    }
}
