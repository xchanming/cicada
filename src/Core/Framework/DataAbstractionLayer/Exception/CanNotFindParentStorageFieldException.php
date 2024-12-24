<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use DataAbstractionLayerException::cannotFindParentStorageField instead
 */
#[Package('core')]
class CanNotFindParentStorageFieldException extends CicadaHttpException
{
    public function __construct(EntityDefinition $definition)
    {
        parent::__construct(
            'Can not find FkField for parent property definition {{ definition }}',
            ['definition' => $definition->getEntityName()]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(__CLASS__, 'v6.7.0.0', 'DataAbstractionLayerException::cannotFindParentStorageField'),
        );

        return DataAbstractionLayerException::CANNOT_FIND_PARENT_STORAGE_FIELD;
    }
}
