<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class ConfigJsonFieldSerializer extends JsonFieldSerializer
{
    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        $wrapped = [ConfigJsonField::STORAGE_KEY => $data->getValue()];
        $data->setValue($wrapped);

        return parent::encode($field, $existence, $data, $parameters);
    }

    public function decode(Field $field, mixed $value): mixed
    {
        $wrapped = parent::decode($field, $value);
        if ($wrapped === null || !isset($wrapped[ConfigJsonField::STORAGE_KEY])) {
            return null;
        }

        return $wrapped[ConfigJsonField::STORAGE_KEY];
    }
}
