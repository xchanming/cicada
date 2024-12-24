<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Service;

use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Cicada\Core\Checkout\Document\DocumentCollection;
use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Document\DocumentGenerationResult;
use Cicada\Core\Checkout\Document\DocumentIdStruct;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Service\DocumentMerger;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Tfpdf\Fpdi;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DocumentMerger::class)]
class DocumentMergerTest extends TestCase
{
    public function testMergeWithFpdiConfig(): void
    {
        $fpdi = $this->createMock(Fpdi::class);
        $fpdi->expects(static::exactly(1))
            ->method('setSourceFile');

        $orderId = Uuid::randomHex();

        $documentType = new DocumentTypeEntity();
        $documentType->setId(Uuid::randomHex());
        $documentType->setTechnicalName('invoice');

        $firstDocument = new DocumentEntity();
        $firstDocument->setId(Uuid::randomHex());
        $firstDocument->setOrderId($orderId);
        $firstDocument->setDocumentTypeId($documentType->getId());
        $firstDocument->setDocumentType($documentType);
        $firstDocument->setStatic(false);
        $firstDocument->setConfig([]);

        $secondDocument = new DocumentEntity();
        $secondDocument->setId(Uuid::randomHex());
        $secondDocument->setOrderId($orderId);
        $secondDocument->setStatic(false);
        $secondDocument->setConfig([]);

        /** @var StaticEntityRepository<DocumentCollection> $documentRepository */
        $documentRepository = new StaticEntityRepository([
            new EntitySearchResult(
                'document',
                2,
                new DocumentCollection([$firstDocument, $secondDocument]),
                null,
                new Criteria(),
                Context::createDefaultContext(),
            ),
        ]);

        $documentGenerator = $this->createMock(DocumentGenerator::class);
        $documentGenerator->expects(static::exactly(1))->method('generate')->willReturnCallback(function (string $documentType, array $operations) {
            $ids = array_keys($operations);
            $result = new DocumentGenerationResult();

            $result->addSuccess(new DocumentIdStruct($ids[0], '', Uuid::randomHex()));

            return $result;
        });

        $mediaService = $this->createMock(MediaService::class);
        $mediaService->expects(static::once())
            ->method('loadFileStream')
            ->willReturnCallback(function () {
                return Utils::streamFor();
            });

        $documentMerger = new DocumentMerger(
            $documentRepository,
            $mediaService,
            $documentGenerator,
            $fpdi
        );

        $documentMerger->merge([Uuid::randomHex()], Context::createDefaultContext());
    }
}
