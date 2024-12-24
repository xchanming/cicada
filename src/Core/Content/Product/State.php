<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
final class State
{
    public const IS_PHYSICAL = 'is-physical';
    public const IS_DOWNLOAD = 'is-download';
}
