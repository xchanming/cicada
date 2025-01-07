<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataAbstractionLayer\Field;

use Cicada\Core\Content\Cms\DataAbstractionLayer\FieldSerializer\SlotConfigFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class SlotConfigField extends JsonField
{
    public function __construct(
        string $storageName,
        string $propertyName
    ) {
        $this->storageName = $storageName;
        parent::__construct($storageName, $propertyName);
    }

    protected function getSerializerClass(): string
    {
        return SlotConfigFieldSerializer::class;
    }
}
