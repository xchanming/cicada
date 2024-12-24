<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\MessageQueueException;
use Cicada\Core\Framework\MessageQueue\Service\MessageSizeCalculator;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;

#[Package('core')]
readonly class MessageQueueSizeRestrictListener
{
    /**
     * @see https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/quotas-messages.html
     * Maximum message size is 262144 (1024 * 256) bytes
     */
    private const MESSAGE_SIZE_LIMIT = 1024 * 256;

    /**
     * @internal
     */
    public function __construct(
        private MessageSizeCalculator $calculator,
        private bool $enforceMessageSize
    ) {
    }

    public function __invoke(SendMessageToTransportsEvent $event): void
    {
        if (!$this->enforceMessageSize) {
            return;
        }

        /**
         * If the message is sent to the SyncTransport, it means that the message is not sent to any other transport so it can be ignored.
         */
        foreach ($event->getSenders() as $sender) {
            if ($sender instanceof SyncTransport) {
                return;
            }
        }

        $messageLengthInBytes = $this->calculator->size($event->getEnvelope());
        if ($messageLengthInBytes > self::MESSAGE_SIZE_LIMIT) {
            $messageName = $event->getEnvelope()->getMessage()::class;

            throw MessageQueueException::queueMessageSizeExceeded($messageName, $messageLengthInBytes / 1024);
        }
    }
}
