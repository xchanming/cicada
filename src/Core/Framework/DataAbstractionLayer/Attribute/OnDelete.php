<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
enum OnDelete: string
{
    case CASCADE = 'CASCADE';
    case SET_NULL = 'SET NULL';
    case RESTRICT = 'RESTRICT';
    case NO_ACTION = 'NO ACTION';
}
