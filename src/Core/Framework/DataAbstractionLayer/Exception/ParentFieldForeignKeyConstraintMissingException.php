<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use DataAbstractionLayerException::parentFieldForeignKeyConstraintMissing instead
 */
#[Package('core')]
class ParentFieldForeignKeyConstraintMissingException extends CicadaHttpException
{
    public function __construct(EntityDefinition $definition, Field $parentField)
    {
        parent::__construct(
            'Foreign key property {{ propertyName }} of parent association in definition {{ definition }} expected to be an FkField got %s',
            [
                'definition' => $definition->getEntityName(),
                'propertyName' => $parentField->getPropertyName(),
                'propertyClass' => $parentField::class,
            ]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(__CLASS__, 'v6.7.0.0', 'DataAbstractionLayerException::parentFieldForeignKeyConstraintMissing'),
        );

        return DataAbstractionLayerException::PARENT_FIELD_KEY_CONSTRAINT_MISSING;
    }
}
