<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Required
{
    public function __construct()
    {
    }
}
