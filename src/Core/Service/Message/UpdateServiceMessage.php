<?php declare(strict_types=1);

namespace Cicada\Core\Service\Message;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * @internal
 */
#[Package('core')]
readonly class UpdateServiceMessage implements AsyncMessageInterface
{
    public function __construct(public string $name)
    {
    }
}
