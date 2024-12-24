<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Service;

use Cicada\Core\Checkout\Document\DocumentCollection;
use Cicada\Core\Checkout\Document\DocumentConfigurationFactory;
use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\RenderedDocument;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Random;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tfpdf\Fpdi;

#[Package('checkout')]
final class DocumentMerger
{
    /**
     * @internal
     *
     * @param EntityRepository<DocumentCollection> $documentRepository
     */
    public function __construct(
        private readonly EntityRepository $documentRepository,
        private readonly MediaService $mediaService,
        private readonly DocumentGenerator $documentGenerator,
        private readonly Fpdi $fpdi
    ) {
    }

    /**
     * @param array<string> $documentIds
     */
    public function merge(array $documentIds, Context $context): ?RenderedDocument
    {
        if (empty($documentIds)) {
            return null;
        }

        $criteria = new Criteria($documentIds);
        $criteria->addAssociation('documentType');
        $criteria->addSorting(new FieldSorting('order.orderNumber'));

        $documents = $this->documentRepository->search($criteria, $context)->getEntities();

        if ($documents->count() === 0) {
            return null;
        }

        $fileName = Random::getAlphanumericString(32) . '.' . PdfRenderer::FILE_EXTENSION;

        if ($documents->count() === 1) {
            $document = $documents->first();
            if ($document === null) {
                return null;
            }

            $documentMediaId = $this->ensureDocumentMediaFileGenerated($document, $context);

            if ($documentMediaId === null) {
                return null;
            }

            $fileBlob = $context->scope(Context::SYSTEM_SCOPE, fn (Context $context): string => $this->mediaService->loadFile($documentMediaId, $context));

            $renderedDocument = new RenderedDocument('', '', $fileName);
            $renderedDocument->setContent($fileBlob);

            return $renderedDocument;
        }

        $totalPage = 0;
        foreach ($documents as $document) {
            $documentMediaId = $this->ensureDocumentMediaFileGenerated($document, $context);

            if ($documentMediaId === null) {
                continue;
            }

            $config = DocumentConfigurationFactory::createConfiguration($document->getConfig());

            $media = $context->scope(Context::SYSTEM_SCOPE, fn (Context $context): string => $this->mediaService->loadFileStream($documentMediaId, $context)->getContents());

            $numPages = $this->fpdi->setSourceFile(StreamReader::createByString($media));

            $totalPage += $numPages;
            for ($i = 1; $i <= $numPages; ++$i) {
                $template = $this->fpdi->importPage($i);
                $size = $this->fpdi->getTemplateSize($template);
                if (!\is_array($size)) {
                    continue;
                }
                $this->fpdi->AddPage($config->getPageOrientation() ?? 'portrait', $config->getPageSize());
                $this->fpdi->useTemplate($template);
            }
        }

        if ($totalPage === 0) {
            return null;
        }

        $renderedDocument = new RenderedDocument('', '', $fileName);

        $renderedDocument->setContent($this->fpdi->Output($fileName, 'S'));
        $renderedDocument->setContentType(PdfRenderer::FILE_CONTENT_TYPE);
        $renderedDocument->setName($fileName);

        return $renderedDocument;
    }

    private function ensureDocumentMediaFileGenerated(DocumentEntity $document, Context $context): ?string
    {
        $documentMediaId = $document->getDocumentMediaFileId();

        if ($documentMediaId !== null || $document->isStatic()) {
            return $documentMediaId;
        }

        $operation = new DocumentGenerateOperation(
            $document->getOrderId(),
            FileTypes::PDF,
            $document->getConfig(),
            $document->getReferencedDocumentId()
        );

        $operation->setDocumentId($document->getId());

        $documentType = $document->getDocumentType();
        if ($documentType === null) {
            return null;
        }

        $documentStruct = $this->documentGenerator->generate(
            $documentType->getTechnicalName(),
            [$document->getOrderId() => $operation],
            $context
        )->getSuccess()->first();

        if ($documentStruct === null) {
            return null;
        }

        $documentMediaId = $documentStruct->getMediaId();
        $document->setDocumentMediaFileId($documentMediaId);

        return $documentMediaId;
    }
}
