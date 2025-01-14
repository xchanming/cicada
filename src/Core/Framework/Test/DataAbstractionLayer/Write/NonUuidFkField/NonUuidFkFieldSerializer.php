<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Write\NonUuidFkField;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;

/**
 * @internal test class
 */
class NonUuidFkFieldSerializer implements FieldSerializerInterface
{
    /**
     * @param NonUuidFkField $field
     */
    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        yield $field->getStorageName() => $data->getValue();
    }

    public function decode(Field $field, mixed $value): mixed
    {
        return $value;
    }

    /**
     * @param array<string, array<string, mixed>> $data
     *
     * @return array<string, array<string, mixed>>
     */
    public function normalize(Field $field, array $data, WriteParameterBag $parameters): array
    {
        return $data;
    }
}
