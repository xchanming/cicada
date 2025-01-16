<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Struct;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal not intended for decoration or replacement
 */
#[Package('after-sales')]
class ActionSequence extends Sequence
{
    public string $action;

    public array $config = [];

    public ?Sequence $nextAction = null;

    public ?string $appFlowActionId = null;
}
