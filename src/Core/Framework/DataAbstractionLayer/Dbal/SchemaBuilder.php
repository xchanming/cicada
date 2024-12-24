<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Cicada\Core\Content\Cms\DataAbstractionLayer\Field\SlotConfigField;
use Cicada\Core\Content\Flow\DataAbstractionLayer\Field\FlowTemplateConfigField;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CronIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
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
use Cicada\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\SerializedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StateMachineStateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TaxFreeConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeBreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeLevelField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreePathField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VariantListingConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionDataPayloadField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;

/**
 * @internal
 */
#[Package('core')]
class SchemaBuilder
{
    /**
     * @var array<string, string>
     */
    public static array $fieldMapping = [
        IdField::class => Types::BINARY,
        FkField::class => Types::BINARY,
        ParentFkField::class => Types::BINARY,
        VersionField::class => Types::BINARY,
        ReferenceVersionField::class => Types::BINARY,
        CreatedByField::class => Types::BINARY,
        UpdatedByField::class => Types::BINARY,
        StateMachineStateField::class => Types::BINARY,

        CreatedAtField::class => Types::DATETIME_MUTABLE,
        UpdatedAtField::class => Types::DATETIME_MUTABLE,
        DateTimeField::class => Types::DATETIME_MUTABLE,

        DateField::class => Types::DATE_MUTABLE,
        SerializedField::class => Types::JSON,
        CartPriceField::class => Types::JSON,
        CalculatedPriceField::class => Types::JSON,
        PriceField::class => Types::JSON,
        PriceDefinitionField::class => Types::JSON,
        JsonField::class => Types::JSON,
        ListField::class => Types::JSON,
        ConfigJsonField::class => Types::JSON,
        CustomFields::class => Types::JSON,
        BreadcrumbField::class => Types::JSON,
        CashRoundingConfigField::class => Types::JSON,
        ObjectField::class => Types::JSON,
        TaxFreeConfigField::class => Types::JSON,
        TreeBreadcrumbField::class => Types::JSON,
        VariantListingConfigField::class => Types::JSON,
        VersionDataPayloadField::class => Types::JSON,
        ManyToManyIdField::class => Types::JSON,
        SlotConfigField::class => Types::JSON,
        FlowTemplateConfigField::class => Types::JSON,
        CheapestPriceField::class => Types::JSON,

        ChildCountField::class => Types::INTEGER,
        IntField::class => Types::INTEGER,
        AutoIncrementField::class => Types::INTEGER,
        TreeLevelField::class => Types::INTEGER,

        BoolField::class => Types::BOOLEAN,
        LockedField::class => Types::BOOLEAN,

        PasswordField::class => Types::STRING,
        StringField::class => Types::STRING,
        TimeZoneField::class => Types::STRING,
        CronIntervalField::class => Types::STRING,
        DateIntervalField::class => Types::STRING,
        EmailField::class => Types::STRING,
        RemoteAddressField::class => Types::STRING,
        NumberRangeField::class => Types::STRING,

        BlobField::class => Types::BLOB,

        FloatField::class => Types::DECIMAL,

        TreePathField::class => Types::TEXT,
        LongTextField::class => Types::TEXT,
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    public static array $options = [
        Types::BINARY => [
            'length' => 16,
            'fixed' => true,
        ],

        Types::BOOLEAN => [
            'default' => 0,
        ],
    ];

    public function buildSchemaOfDefinition(EntityDefinition $definition): Table
    {
        $table = (new Schema())->createTable($definition->getEntityName());

        /** @var Field $field */
        foreach ($definition->getFields() as $field) {
            if ($field->is(Runtime::class)) {
                continue;
            }

            if ($field instanceof AssociationField) {
                continue;
            }

            if (!$field instanceof StorageAware) {
                continue;
            }

            if ($field instanceof TranslatedField) {
                continue;
            }

            $fieldType = $this->getFieldType($field);

            $table->addColumn(
                $field->getStorageName(),
                $fieldType,
                $this->getFieldOptions($field, $fieldType, $definition)
            );
        }

        /** @var StorageAware[] $primaryKeys */
        $primaryKeys = $definition->getPrimaryKeys()->filter(function (Field $field) {
            return $field instanceof StorageAware;
        })->getElements();

        $table->setPrimaryKey(array_map(function (StorageAware $field) {
            return $field->getStorageName();
        }, $primaryKeys));

        $this->addForeignKeys($table, $definition);

        return $table;
    }

    private function getFieldType(Field $field): string
    {
        foreach (self::$fieldMapping as $class => $type) {
            if ($field instanceof $class) {
                return self::$fieldMapping[$field::class];
            }
        }

        throw DataAbstractionLayerException::fieldHasNoType($field->getPropertyName());
    }

    /**
     * @return array<string, mixed>
     */
    private function getFieldOptions(Field $field, string $type, EntityDefinition $definition): array
    {
        $options = self::$options[$type] ?? [];

        $options['notnull'] = false;

        if ($field->is(Required::class) && !$field instanceof UpdatedAtField && !$field instanceof ReferenceVersionField) {
            $options['notnull'] = true;
        }

        if (\array_key_exists($field->getPropertyName(), $definition->getDefaults())) {
            $options['default'] = $definition->getDefaults()[$field->getPropertyName()];
        }

        if ($field instanceof StringField) {
            $options['length'] = $field->getMaxLength();
        }

        if ($field instanceof AutoIncrementField) {
            $options['autoincrement'] = true;
            $options['notnull'] = true;
        }

        if ($field instanceof FloatField) {
            $options['precision'] = 10;
            $options['scale'] = 2;
        }

        return $options;
    }

    private function addForeignKeys(Table $table, EntityDefinition $definition): void
    {
        $fields = $definition->getFields()->filter(
            function (Field $field) {
                if ($field instanceof ManyToOneAssociationField
                    || ($field instanceof OneToOneAssociationField && $field->getStorageName() !== 'id')) {
                    return true;
                }

                return false;
            }
        );

        $referenceVersionFields = $definition->getFields()->filterInstance(ReferenceVersionField::class);

        /** @var ManyToOneAssociationField $field */
        foreach ($fields as $field) {
            $reference = $field->getReferenceDefinition();

            $hasOneToMany = $definition->getFields()->filter(function (Field $field) use ($reference) {
                if (!$field instanceof OneToManyAssociationField) {
                    return false;
                }
                if ($field instanceof ChildrenAssociationField) {
                    return false;
                }

                return $field->getReferenceDefinition() === $reference;
            })->count() > 0;

            // skip foreign key to prevent bi-directional foreign key
            if ($hasOneToMany) {
                continue;
            }

            $columns = [
                $field->getStorageName(),
            ];

            $referenceColumns = [
                $field->getReferenceField(),
            ];

            if ($reference->isVersionAware()) {
                $versionField = null;

                /** @var ReferenceVersionField $referenceVersionField */
                foreach ($referenceVersionFields as $referenceVersionField) {
                    if ($referenceVersionField->getVersionReferenceDefinition() === $reference) {
                        $versionField = $referenceVersionField;

                        break;
                    }
                }

                if ($field instanceof ParentAssociationField) {
                    $columns[] = 'version_id';
                } else {
                    /** @var ReferenceVersionField $versionField */
                    $columns[] = $versionField->getStorageName();
                }

                $referenceColumns[] = 'version_id';
            }

            $update = 'CASCADE';

            if ($field->is(CascadeDelete::class)) {
                $delete = 'CASCADE';
            } elseif ($field->is(RestrictDelete::class)) {
                $delete = 'RESTRICT';
            } else {
                $delete = 'SET NULL';
            }

            $table->addForeignKeyConstraint(
                $reference->getEntityName(),
                $columns,
                $referenceColumns,
                [
                    'onUpdate' => $update,
                    'onDelete' => $delete,
                ],
                \sprintf('fk.%s.%s', $definition->getEntityName(), $field->getStorageName())
            );
        }
    }
}
