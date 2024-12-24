<?php declare(strict_types=1);

namespace Cicada\Core\Content\Mail\Message;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * @codeCoverageIgnore
 */
#[Package('services-settings')]
class SendMailMessage implements AsyncMessageInterface
{
    /**
     * @internal
     */
    public function __construct(public readonly string $mailDataPath)
    {
    }
}
