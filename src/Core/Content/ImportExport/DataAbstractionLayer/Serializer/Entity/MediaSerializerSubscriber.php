<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\Media\MediaEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class MediaSerializerSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AbstractMediaSerializer $mediaSerializer)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MediaEvents::MEDIA_WRITTEN_EVENT => 'forward',
        ];
    }

    public function forward(EntityWrittenEvent $event): void
    {
        $this->mediaSerializer->persistMedia($event);
    }
}
