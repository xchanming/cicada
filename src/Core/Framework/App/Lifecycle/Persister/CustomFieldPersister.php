<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Persister;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFields;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldSet;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Cicada\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationCollection;
use Cicada\Core\System\CustomField\CustomFieldCollection;
use Doctrine\DBAL\Connection;

/**
 * @internal only for use by the app-system
 *
 * @phpstan-import-type CustomFieldSetArray from CustomFieldSet
 */
#[Package('core')]
class CustomFieldPersister
{
    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     * @param EntityRepository<CustomFieldSetRelationCollection> $customFieldSetRelationRepository
     * @param EntityRepository<CustomFieldCollection> $customFieldRepository
     */
    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly Connection $connection,
        private readonly EntityRepository $customFieldSetRelationRepository,
        private readonly EntityRepository $customFieldRepository,
    ) {
    }

    /**
     * @internal only for use by the app-system
     */
    public function updateCustomFields(Manifest $manifest, string $appId, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($manifest, $appId): void {
            $this->upsertCustomFieldSets($manifest->getCustomFields(), $appId, $context);
        });
    }

    private function upsertCustomFieldSets(?CustomFields $customFields, string $appId, Context $context): void
    {
        $existingCustomFieldSets = Uuid::fromBytesToHexList(
            $this->connection->fetchAllKeyValue(
                'SELECT name, id FROM custom_field_set WHERE app_id = :appId',
                ['appId' => Uuid::fromHexToBytes($appId)]
            )
        );

        if (!$customFields || empty($customFields->getCustomFieldSets())) {
            if (!empty($existingCustomFieldSets)) {
                $this->deleteObsoleteIds(
                    array_values($existingCustomFieldSets),
                    [],
                    [],
                    $context
                );
            }

            return;
        }

        $payload = [];
        $obsoleteRelations = [];
        $obsoleteFields = [];

        foreach ($customFields->getCustomFieldSets() as $customFieldSet) {
            if (!\array_key_exists($customFieldSet->getName(), $existingCustomFieldSets)) {
                $existingRelations = $existingFields = [];
                $payload[] = $customFieldSet->toEntityArray($appId, $existingRelations, $existingFields);

                continue;
            }

            $existingRelations = Uuid::fromBytesToHexList(
                $this->connection->fetchAllKeyValue(
                    'SELECT entity_name, id FROM custom_field_set_relation WHERE set_id = :setId',
                    ['setId' => Uuid::fromHexToBytes($existingCustomFieldSets[$customFieldSet->getName()])]
                )
            );
            $existingFields = Uuid::fromBytesToHexList(
                $this->connection->fetchAllKeyValue(
                    'SELECT name, id FROM custom_field WHERE set_id = :setId',
                    ['setId' => Uuid::fromHexToBytes($existingCustomFieldSets[$customFieldSet->getName()])]
                )
            );
            $entityData = $customFieldSet->toEntityArray($appId, $existingRelations, $existingFields);
            $entityData['id'] = $existingCustomFieldSets[$customFieldSet->getName()];

            $obsoleteRelations = array_merge($obsoleteRelations, array_values($existingRelations));
            $obsoleteFields = array_merge($obsoleteFields, array_values($existingFields));

            $payload[] = $entityData;
            unset($existingCustomFieldSets[$customFieldSet->getName()]);
        }

        $this->deleteObsoleteIds(
            array_values($existingCustomFieldSets),
            $obsoleteRelations,
            $obsoleteFields,
            $context
        );

        $this->customFieldSetRepository->upsert($payload, $context);
    }

    /**
     * @param list<string> $obsoleteFieldSets
     * @param list<string> $obsoleteRelations
     * @param list<string> $obsoleteFields
     */
    private function deleteObsoleteIds(array $obsoleteFieldSets, array $obsoleteRelations, array $obsoleteFields, Context $context): void
    {
        if (!empty($obsoleteFieldSets)) {
            $ids = array_map(static fn (string $id): array => ['id' => $id], $obsoleteFieldSets);

            $this->customFieldSetRepository->delete($ids, $context);
        }

        if (!empty($obsoleteRelations)) {
            $ids = array_map(static fn (string $id): array => ['id' => $id], $obsoleteRelations);

            $this->customFieldSetRelationRepository->delete($ids, $context);
        }

        if (!empty($obsoleteFields)) {
            $ids = array_map(static fn (string $id): array => ['id' => $id], $obsoleteFields);

            $this->customFieldRepository->delete($ids, $context);
        }
    }
}
