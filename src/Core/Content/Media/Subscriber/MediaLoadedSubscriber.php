<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Subscriber;

use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class MediaLoadedSubscriber
{
    public function unserialize(EntityLoadedEvent $event): void
    {
        /** @var MediaEntity $media */
        foreach ($event->getEntities() as $media) {
            if ($media->getMediaTypeRaw()) {
                $media->setMediaType(unserialize($media->getMediaTypeRaw()));
            }

            if ($media->getThumbnails() !== null) {
                continue;
            }

            $thumbnails = match (true) {
                $media->getThumbnailsRo() !== null => unserialize($media->getThumbnailsRo()),
                default => new MediaThumbnailCollection(),
            };

            $media->setThumbnails($thumbnails);
        }
    }
}
