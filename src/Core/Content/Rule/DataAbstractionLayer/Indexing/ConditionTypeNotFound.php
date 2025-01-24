<?php declare(strict_types=1);

namespace Cicada\Core\Content\Rule\DataAbstractionLayer\Indexing;

use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class ConditionTypeNotFound extends \RuntimeException
{
}
