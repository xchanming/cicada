<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomField\CustomFieldTypes;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class PriceField extends CustomFieldType
{
    protected function toEntityArray(): array
    {
        return [
            'type' => CustomFieldTypes::PRICE,
            'config' => [
                'type' => 'price',
                'componentName' => 'sw-price-field',
                'customFieldType' => 'price',
            ],
        ];
    }
}
