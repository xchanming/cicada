<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Xml\Field;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\Xml\Field\Traits\RequiredTrait;
use Cicada\Core\System\CustomEntity\Xml\Field\Traits\TranslatableTrait;

/**
 * @internal
 */
#[Package('core')]
class EmailField extends Field
{
    use RequiredTrait;
    use TranslatableTrait;

    protected string $type = 'email';

    protected ?string $default = null;

    public function getDefault(): ?string
    {
        return $this->default;
    }
}
