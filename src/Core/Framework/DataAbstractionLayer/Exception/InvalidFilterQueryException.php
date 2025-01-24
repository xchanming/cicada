<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class InvalidFilterQueryException extends DataAbstractionLayerException
{
}
