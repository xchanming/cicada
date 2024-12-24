<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use DataAbstractionLayerException::invalidParentAssociation instead
 */
#[Package('core')]
class InvalidParentAssociationException extends CicadaHttpException
{
    public function __construct(
        EntityDefinition $definition,
        Field $parentField
    ) {
        parent::__construct(
            'Parent property for {{ definition }} expected to be an ManyToOneAssociationField got {{ fieldDefinition }}',
            ['definition' => $definition->getEntityName(), 'fieldDefinition' => $parentField::class]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(__CLASS__, 'v6.7.0.0', 'DataAbstractionLayerException::invalidParentAssociation'),
        );

        return DataAbstractionLayerException::INVALID_PARENT_ASSOCIATION_EXCEPTION;
    }
}
