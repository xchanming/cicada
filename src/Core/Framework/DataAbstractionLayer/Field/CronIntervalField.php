<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CronIntervalFieldSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class CronIntervalField extends Field implements StorageAware
{
    private string $storageName;

    public function __construct(
        string $storageName,
        string $propertyName
    ) {
        $this->storageName = $storageName;
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return CronIntervalFieldSerializer::class;
    }
}
