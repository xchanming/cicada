<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\Service;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SerializedMessageStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @internal
 */
#[Package('framework')]
class MessageSizeCalculator
{
    /**
     * @internal
     */
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function size(Envelope $envelope): int
    {
        $stamp = $envelope->last(SerializedMessageStamp::class);

        return \strlen(
            $stamp?->getSerializedMessage()
            ?? json_encode($this->serializer->encode($envelope), \JSON_THROW_ON_ERROR)
        );
    }
}
