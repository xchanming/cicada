<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\ApiDefinition\Generator;

use Cicada\Core\Framework\Api\ApiDefinition\ApiDefinitionGeneratorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\ReadProtection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Flag;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeLevelField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreePathField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionDataPayloadField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\Schema\DynamicEntityDefinition;

/**
 * @internal
 */
#[Package('core')]
class EntitySchemaGenerator implements ApiDefinitionGeneratorInterface
{
    final public const FORMAT = 'entity-schema';

    public function supports(string $format, string $api): bool
    {
        return $format === self::FORMAT;
    }

    public function generate(array $definitions, string $api, string $apiType = 'jsonapi', ?string $bundleName = null): never
    {
        throw new \RuntimeException();
    }

    /**
     * @return array<
     *     string,
     *     array{
     *          entity: string,
     *          properties: array<string, array{type: string, flags: array<string, mixed>}>,
     *          write-protected: bool,
     *          read-protected: bool,
     *          flags?: list<Flag>
     *      }
     * >
     */
    public function getSchema(array $definitions): array
    {
        $schema = [];

        ksort($definitions);

        foreach ($definitions as $definition) {
            $entity = $definition->getEntityName();

            $entitySchema = $this->getEntitySchema($definition);

            if ($entitySchema['write-protected'] && $entitySchema['read-protected']) {
                continue;
            }

            $schema[$entity] = $entitySchema;
        }

        return $schema;
    }

    /**
     * @return array{
     *     entity: string,
     *     properties: array<string, array{type: string, flags: array<string, mixed>}>,
     *     write-protected: bool,
     *     read-protected: bool,
     *     flags?: list<Flag>
     *  }
     */
    private function getEntitySchema(EntityDefinition $definition): array
    {
        $fields = $definition->getFields();

        $properties = [];
        foreach ($fields as $field) {
            $properties[$field->getPropertyName()] = $this->parseField($definition, $field);
        }

        $result = [
            'entity' => $definition->getEntityName(),
            'properties' => $properties,
            'write-protected' => $definition->getProtections()->get(WriteProtection::class) !== null,
            'read-protected' => $definition->getProtections()->get(ReadProtection::class) !== null,
        ];

        if ($definition instanceof DynamicEntityDefinition) {
            $result['flags'] = $definition->getFlags();
        }

        return $result;
    }

    /**
     * @return array{type: string, flags: array<string, mixed>}
     */
    private function parseField(EntityDefinition $definition, Field $field): array
    {
        $flags = [];
        foreach ($field->getFlags() as $flag) {
            $flags = array_replace_recursive($flags, iterator_to_array($flag->parse()));
        }

        switch (true) {
            case $field instanceof TranslatedField:
                $property = $this->parseField(
                    $definition,
                    EntityDefinitionQueryHelper::getTranslatedField($definition, $field)
                );
                $property['flags'] = array_replace_recursive($property['flags'], $flags);
                $property['flags']['translatable'] = true;

                return $property;

                // fields with uuid
            case $field instanceof VersionField:
            case $field instanceof ReferenceVersionField:
            case $field instanceof ParentFkField:
            case $field instanceof FkField:
            case $field instanceof IdField:
                return ['type' => 'uuid', 'flags' => $flags];

                // json fields
            case $field instanceof CustomFields:
            case $field instanceof VersionDataPayloadField:
            case $field instanceof CalculatedPriceField:
            case $field instanceof CartPriceField:
            case $field instanceof PriceDefinitionField:
            case $field instanceof PriceField:
            case $field instanceof ObjectField:
                return $this->createJsonObjectType($definition, $field, $flags);

            case $field instanceof ListField:
            case $field instanceof BreadcrumbField:
                return ['type' => 'json_list', 'flags' => $flags];

            case $field instanceof JsonField:
                return $this->createJsonObjectType($definition, $field, $flags);

                // association fields
            case $field instanceof ChildrenAssociationField:
            case $field instanceof TranslationsAssociationField:
            case $field instanceof OneToManyAssociationField:
                $reference = $field->getReferenceDefinition();
                $localField = $definition->getFields()->getByStorageName($field->getLocalField());
                $referenceField = $reference->getFields()->getByStorageName($field->getReferenceField());

                $primary = $reference->getPrimaryKeys()->first();
                if (!$primary) {
                    throw new \RuntimeException(\sprintf('No primary key defined for %s', $reference->getEntityName()));
                }

                return [
                    'type' => 'association',
                    'relation' => 'one_to_many',
                    'entity' => $reference->getEntityName(),
                    'flags' => $flags,
                    'localField' => $localField ? $localField->getPropertyName() : null,
                    'referenceField' => $referenceField ? $referenceField->getPropertyName() : null,
                    'primary' => $primary->getPropertyName(),
                ];

            case $field instanceof ParentAssociationField:
            case $field instanceof ManyToOneAssociationField:
                $reference = $field->getReferenceDefinition();
                $localField = $definition->getFields()->getByStorageName($field->getStorageName());
                $referenceField = $reference->getFields()->getByStorageName($field->getReferenceField());

                return [
                    'type' => 'association',
                    'relation' => 'many_to_one',
                    'entity' => $reference->getEntityName(),
                    'flags' => $flags,
                    'localField' => $localField ? $localField->getPropertyName() : null,
                    'referenceField' => $referenceField ? $referenceField->getPropertyName() : null,
                ];

            case $field instanceof ManyToManyAssociationField:
                $reference = $field->getToManyReferenceDefinition();
                $localField = $definition->getFields()->getByStorageName($field->getLocalField());
                $referenceField = $reference->getFields()->getByStorageName($field->getReferenceField());

                $mappingReference = $field->getMappingDefinition()->getFields()->getByStorageName(
                    $field->getMappingReferenceColumn()
                );
                $mappingLocal = $field->getMappingDefinition()->getFields()->getByStorageName(
                    $field->getMappingLocalColumn()
                );

                if (!$mappingReference) {
                    throw new \RuntimeException(\sprintf('Can not find mapping entity field for storage field %s', $field->getMappingReferenceColumn()));
                }
                if (!$mappingLocal) {
                    throw new \RuntimeException(\sprintf('Can not find mapping entity field for storage field %s', $field->getMappingLocalColumn()));
                }

                return [
                    'type' => 'association',
                    'relation' => 'many_to_many',
                    'local' => $mappingLocal->getPropertyName(),
                    'reference' => $mappingReference->getPropertyName(),
                    'mapping' => $field->getMappingDefinition()->getEntityName(),
                    'entity' => $field->getToManyReferenceDefinition()->getEntityName(),
                    'flags' => $flags,
                    'localField' => $localField ? $localField->getPropertyName() : null,
                    'referenceField' => $referenceField ? $referenceField->getPropertyName() : null,
                ];

            case $field instanceof OneToOneAssociationField:
                $reference = $field->getReferenceDefinition();

                $localField = $definition->getFields()->getByStorageName($field->getStorageName());
                $referenceField = $reference->getFields()->getByStorageName($field->getReferenceField());

                return [
                    'type' => 'association',
                    'relation' => 'one_to_one',
                    'entity' => $reference->getEntityName(),
                    'flags' => $flags,
                    'localField' => $localField ? $localField->getPropertyName() : null,
                    'referenceField' => $referenceField ? $referenceField->getPropertyName() : null,
                ];

                // int fields
            case $field instanceof ChildCountField:
            case $field instanceof TreeLevelField:
            case $field instanceof IntField:
                return ['type' => 'int', 'flags' => $flags];

                // long text fields
            case $field instanceof TreePathField:
            case $field instanceof LongTextField:
                return ['type' => 'text', 'flags' => $flags];

                // date fields
            case $field instanceof UpdatedAtField:
            case $field instanceof CreatedAtField:
            case $field instanceof DateTimeField:
            case $field instanceof DateField:
                return ['type' => 'date', 'flags' => $flags];

                // scalar fields
            case $field instanceof PasswordField:
                return ['type' => 'password', 'flags' => $flags];

            case $field instanceof FloatField:
                return ['type' => 'float', 'flags' => $flags];

            case $field instanceof StringField:
                return ['type' => 'string', 'flags' => $flags];

            case $field instanceof BlobField:
                return ['type' => 'blob', 'flags' => $flags];

            case $field instanceof BoolField:
                return ['type' => 'boolean', 'flags' => $flags];

            default:
                return ['type' => $field::class, 'flags' => $flags];
        }
    }

    /**
     * @param array<string, mixed> $flags
     *
     * @return array{
     *     type: string,
     *     properties: array<string,
     *     array{type: string, flags: array<string, mixed>}>,
     *     flags: array<string, mixed>
     * }
     */
    private function createJsonObjectType(EntityDefinition $definition, Field $field, array $flags): array
    {
        $nested = [];
        if ($field instanceof JsonField) {
            foreach ($field->getPropertyMapping() as $nestedField) {
                $nested[$nestedField->getPropertyName()] = $this->parseField($definition, $nestedField);
            }
        }

        return [
            'type' => 'json_object',
            'properties' => $nested,
            'flags' => $flags,
        ];
    }
}
