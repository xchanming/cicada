<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Exception;

use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class ExecuteSequenceException extends \Exception
{
    public function __construct(
        private readonly string $flowId,
        private readonly string $sequenceId,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getFlowId(): string
    {
        return $this->flowId;
    }

    public function getSequenceId(): string
    {
        return $this->sequenceId;
    }
}
