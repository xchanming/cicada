<?php
declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\Constraint\Uuid as UuidConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('core')]
class FkFieldSerializer extends AbstractFieldSerializer
{
    public function normalize(Field $field, array $data, WriteParameterBag $parameters): array
    {
        if (!$field instanceof FkField) {
            throw DataAbstractionLayerException::invalidSerializerField(FkField::class, $field);
        }

        $value = $data[$field->getPropertyName()] ?? null;

        $writeContext = $parameters->getContext();

        if ($this->shouldUseContext($field, true, $value) && $writeContext->has($field->getReferenceDefinition()->getEntityName(), $field->getReferenceField())) {
            $data[$field->getPropertyName()] = $writeContext->get($field->getReferenceDefinition()->getEntityName(), $field->getReferenceField());
        }

        return $data;
    }

    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof FkField) {
            throw DataAbstractionLayerException::invalidSerializerField(FkField::class, $field);
        }

        $value = $data->getValue();

        if ($this->shouldUseContext($field, $data->isRaw(), $value)) {
            try {
                $value = $parameters->getContext()->get($field->getReferenceDefinition()->getEntityName(), $field->getReferenceField());
            } catch (\InvalidArgumentException) {
                if ($this->requiresValidation($field, $existence, $value, $parameters)) {
                    $this->validate($this->getConstraints($field), $data, $parameters->getPath());
                }
            }
        }

        if ($value === null) {
            yield $field->getStorageName() => null;

            return;
        }
        if ($this->requiresValidation($field, $existence, $value, $parameters)) {
            $this->validate([new UuidConstraint()], $data, $parameters->getPath());
        }

        $value = Uuid::fromHexToBytes($value);

        yield $field->getStorageName() => $value;
    }

    public function decode(Field $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Uuid::fromBytesToHex($value);
    }

    protected function shouldUseContext(FkField $field, bool $isRaw, mixed $value): bool
    {
        return $isRaw && $value === null && $field->is(Required::class);
    }

    protected function getConstraints(Field $field): array
    {
        return [new NotBlank()];
    }
}
