<?php
declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

/**
 * @deprecated tag:v6.7.0 - will be removed, use \Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::decodeHandledByHydrator instead
 */
#[Package('core')]
class DecodeByHydratorException extends CicadaHttpException
{
    public function __construct(Field $field)
    {
        parent::__construct(
            'Decoding of {{ fieldClass }} is handled by the entity hydrator.',
            ['fieldClass' => $field::class]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', DataAbstractionLayerException::class . '::decodeHandledByHydrator')
        );

        return 'FRAMEWORK__DECODING_HANDLED_BY_ENTITY_HYDRATOR';
    }
}
