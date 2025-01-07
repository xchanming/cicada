<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Message;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\Thumbnail\ThumbnailService;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('discovery')]
final class GenerateThumbnailsHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ThumbnailService $thumbnailService,
        private readonly EntityRepository $mediaRepository,
        private readonly bool $remoteThumbnailsEnable = false
    ) {
    }

    public function __invoke(GenerateThumbnailsMessage|UpdateThumbnailsMessage $msg): void
    {
        if ($this->remoteThumbnailsEnable) {
            return;
        }

        $context = $msg->getContext();

        $criteria = new Criteria();
        $criteria->addAssociation('mediaFolder.configuration.mediaThumbnailSizes');
        $criteria->addFilter(new EqualsAnyFilter('media.id', $msg->getMediaIds()));

        /** @var MediaCollection $entities */
        $entities = $this->mediaRepository->search($criteria, $context)->getEntities();

        if ($msg instanceof UpdateThumbnailsMessage) {
            foreach ($entities as $media) {
                $this->thumbnailService->updateThumbnails($media, $context, $msg->isStrict());
            }
        } else {
            $this->thumbnailService->generate($entities, $context);
        }
    }
}
