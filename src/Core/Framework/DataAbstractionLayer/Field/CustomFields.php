<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\CustomFieldsAccessorBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CustomFieldsSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class CustomFields extends JsonField
{
    public function __construct(
        string $storageName = 'custom_fields',
        string $propertyName = 'customFields'
    ) {
        parent::__construct($storageName, $propertyName);
    }

    /**
     * @param list<Field> $propertyMapping
     */
    public function setPropertyMapping(array $propertyMapping): void
    {
        $this->propertyMapping = $propertyMapping;
    }

    protected function getSerializerClass(): string
    {
        return CustomFieldsSerializer::class;
    }

    protected function getAccessorBuilderClass(): ?string
    {
        return CustomFieldsAccessorBuilder::class;
    }
}
