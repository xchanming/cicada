<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\Message;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('core')]
class CleanupOldCacheFolders implements AsyncMessageInterface
{
}
