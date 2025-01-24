<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class InvalidCriteriaIdsException extends DataAbstractionLayerException
{
}
