<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Service;

use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Content\MailTemplate\Service\Event\AttachmentLoaderCriteriaEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated tag:v6.7.0 - Will be removed as the service is not used anymore
 */
#[Package('buyers-experience')]
class AttachmentLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $documentRepository,
        private readonly DocumentGenerator $documentGenerator,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string> $documentIds
     *
     * @return array<array<string, string>>
     */
    public function load(array $documentIds, Context $context): array
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.7.0.0')
        );

        $attachments = [];
        $criteria = new Criteria($documentIds);
        $criteria->addAssociation('documentMediaFile');
        $criteria->addAssociation('documentType');

        $criteriaEvent = new AttachmentLoaderCriteriaEvent($criteria);
        $this->eventDispatcher->dispatch($criteriaEvent);

        $entities = $this->documentRepository->search($criteria, $context);

        /** @var DocumentEntity $document */
        foreach ($entities as $document) {
            $document = $this->documentGenerator->readDocument($document->getId(), $context);

            if ($document === null) {
                continue;
            }

            $attachments[] = [
                'content' => $document->getContent(),
                'fileName' => $document->getName(),
                'mimeType' => $document->getContentType(),
            ];
        }

        return $attachments;
    }
}
