<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Cicada\Core\Framework\DataAbstractionLayer\FieldType\CronInterval;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;
use Cron\CronExpression;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('checkout')]
class CronIntervalFieldSerializer extends AbstractFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof StorageAware) {
            throw DataAbstractionLayerException::invalidSerializerField(self::class, $field);
        }

        $interval = $data->getValue();

        if ($interval === null) {
            yield $field->getStorageName() => null;

            return;
        }

        if (\is_string($interval)) {
            if (!CronExpression::isValidExpression($interval)) {
                throw DataAbstractionLayerException::invalidCronIntervalFormat($interval);
            }

            $interval = new CronExpression($interval);
        }

        $data->setValue($interval);
        $this->validateIfNeeded($field, $existence, $data, $parameters);

        if (!$interval instanceof CronExpression) {
            yield $field->getStorageName() => null;

            return;
        }

        yield $field->getStorageName() => (string) $interval;
    }

    /**
     * @param string|null $value
     */
    public function decode(Field $field, $value): ?CronInterval
    {
        if ($value === null) {
            return null;
        }

        if (!CronInterval::isValidExpression($value)) {
            throw DataAbstractionLayerException::invalidCronIntervalFormat($value);
        }

        return new CronInterval($value);
    }

    protected function getConstraints(Field $field): array
    {
        return [
            new Type(CronExpression::class),
            new NotNull(),
        ];
    }
}
