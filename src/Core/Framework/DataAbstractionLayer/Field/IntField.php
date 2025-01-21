<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IntFieldSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class IntField extends Field implements StorageAware
{
    public function __construct(
        private readonly string $storageName,
        string $propertyName,
        private readonly ?int $minValue = null,
        private readonly ?int $maxValue = null
    ) {
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    public function getMinValue(): ?int
    {
        return $this->minValue;
    }

    public function getMaxValue(): ?int
    {
        return $this->maxValue;
    }

    protected function getSerializerClass(): string
    {
        return IntFieldSerializer::class;
    }
}
