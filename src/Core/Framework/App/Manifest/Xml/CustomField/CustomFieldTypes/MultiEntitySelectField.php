<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class MultiEntitySelectField extends SingleEntitySelectField
{
    public const COMPONENT_NAME = 'sw-entity-multi-id-select';
}
