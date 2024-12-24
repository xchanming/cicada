<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class SerializedField extends Field implements StorageAware
{
    /**
     * @param class-string<FieldSerializerInterface> $serializer
     */
    public function __construct(
        private readonly string $storageName,
        string $propertyName,
        private readonly string $serializer = JsonFieldSerializer::class
    ) {
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return $this->serializer;
    }
}
