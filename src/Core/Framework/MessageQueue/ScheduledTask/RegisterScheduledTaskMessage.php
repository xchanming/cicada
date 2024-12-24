<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('core')]
class RegisterScheduledTaskMessage implements AsyncMessageInterface
{
}
