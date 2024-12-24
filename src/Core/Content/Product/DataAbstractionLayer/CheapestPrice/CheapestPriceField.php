<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PHPUnserializeFieldSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class CheapestPriceField extends JsonField
{
    public function __construct(
        string $storageName,
        string $propertyName,
        array $propertyMapping = []
    ) {
        parent::__construct($storageName, $propertyName, $propertyMapping);
        $this->addFlags(new WriteProtected());
    }

    protected function getSerializerClass(): string
    {
        return PHPUnserializeFieldSerializer::class;
    }

    protected function getAccessorBuilderClass(): ?string
    {
        return CheapestPriceAccessorBuilder::class;
    }
}
