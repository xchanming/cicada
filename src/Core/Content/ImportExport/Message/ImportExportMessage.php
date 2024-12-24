<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Message;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('services-settings')]
class ImportExportMessage implements AsyncMessageInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Context $context,
        private readonly string $logId,
        private readonly string $activity,
        private readonly int $offset = 0
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getLogId(): string
    {
        return $this->logId;
    }

    public function getActivity(): string
    {
        return $this->activity;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
