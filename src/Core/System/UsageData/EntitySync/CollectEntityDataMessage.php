<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\EntitySync;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\LowPriorityMessageInterface;

/**
 * @internal
 */
#[Package('data-services')]
class CollectEntityDataMessage implements LowPriorityMessageInterface
{
    public function __construct(public readonly ?string $shopId = null)
    {
    }
}
