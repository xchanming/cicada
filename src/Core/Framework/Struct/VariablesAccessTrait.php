<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Struct;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
trait VariablesAccessTrait
{
    /**
     * @return array<string, mixed>
     */
    public function getVars(): array
    {
        return get_object_vars($this);
    }
}
