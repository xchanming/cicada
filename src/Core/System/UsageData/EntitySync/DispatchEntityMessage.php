<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\EntitySync;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\LowPriorityMessageInterface;

/**
 * @internal
 */
#[Package('data-services')]
class DispatchEntityMessage implements LowPriorityMessageInterface
{
    public readonly \DateTimeImmutable $runDate;

    /**
     * @param array<int, array<string, string>> $primaryKeys
     */
    public function __construct(
        public readonly string $entityName,
        public readonly Operation $operation,
        \DateTimeInterface $runDate,
        public readonly array $primaryKeys,
        public readonly ?string $shopId = null
    ) {
        $this->runDate = \DateTimeImmutable::createFromInterface($runDate);
    }
}
