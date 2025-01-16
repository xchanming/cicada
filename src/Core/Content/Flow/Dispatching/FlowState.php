<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching;

use Cicada\Core\Content\Flow\Dispatching\Struct\Sequence;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class FlowState
{
    public string $flowId;

    public bool $stop = false;

    public Sequence $currentSequence;

    public bool $delayed = false;

    public function getSequenceId(): string
    {
        return $this->currentSequence->sequenceId;
    }
}
