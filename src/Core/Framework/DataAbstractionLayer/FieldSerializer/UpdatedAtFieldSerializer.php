<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class UpdatedAtFieldSerializer extends DateTimeFieldSerializer
{
    /**
     * @throws DataAbstractionLayerException
     */
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof UpdatedAtField) {
            throw DataAbstractionLayerException::invalidSerializerField(UpdatedAtField::class, $field);
        }
        if (!$existence->exists()) {
            return;
        }

        $data->setValue(new \DateTime());

        yield from parent::encode($field, $existence, $data, $parameters);
    }
}
