<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Subscriber;

use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationEntity;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('discovery')]
class MediaFolderConfigLoadedSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'media_folder_configuration.loaded' => [
                ['unserialize', 10],
            ],
        ];
    }

    public function unserialize(EntityLoadedEvent $event): void
    {
        /** @var MediaFolderConfigurationEntity $media */
        foreach ($event->getEntities() as $media) {
            if ($media->getMediaThumbnailSizes() === null) {
                if ($media->getMediaThumbnailSizesRo()) {
                    $media->setMediaThumbnailSizes(unserialize($media->getMediaThumbnailSizesRo()));
                } else {
                    $media->setMediaThumbnailSizes(new MediaThumbnailSizeCollection());
                }
            }
        }
    }
}
