<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Struct;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal not intended for decoration or replacement
 */
#[Package('after-sales')]
class IfSequence extends Sequence
{
    public string $ruleId;

    public ?Sequence $falseCase = null;

    public ?Sequence $trueCase = null;
}
