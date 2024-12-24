<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class NumberRangeField extends StringField
{
    public function __construct(
        string $storageName,
        string $propertyName,
        int $maxLength = 64
    ) {
        parent::__construct($storageName, $propertyName, $maxLength);
    }
}
