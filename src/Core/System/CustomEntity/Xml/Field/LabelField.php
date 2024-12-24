<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Xml\Field;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class LabelField extends Field
{
    protected string $type = 'label';
}
