<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Service;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Content\ImportExport\Service\FileService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class FileServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @param array<string, string> $fileData
     */
    #[DataProvider('fileTypesProvider')]
    public function testDetectType(array $fileData): void
    {
        $fileService = new FileService(
            static::getContainer()->get('cicada.filesystem.private'),
            static::getContainer()->get('import_export_file.repository')
        );

        $filePath = $fileData['file'];
        $file = fopen($filePath, 'w');
        static::assertIsResource($file);
        fwrite($file, (string) $fileData['content']);
        fclose($file);

        $uploadedFile = new UploadedFile($filePath, $filePath, $fileData['providedType']);

        $detectedType = $fileService->detectType($uploadedFile);
        static::assertSame($fileData['expectedType'], $detectedType);

        unlink($filePath);
    }

    public function testStoreFile(): void
    {
        /** @var EntityRepository $fileRepository */
        $fileRepository = static::getContainer()->get('import_export_file.repository');
        $fileService = new FileService(
            static::getContainer()->get('cicada.filesystem.private'),
            $fileRepository
        );

        $storedFile = $fileService->storeFile(
            Context::createDefaultContext(),
            new \DateTimeImmutable(),
            null,
            'testfile.csv',
            ImportExportLogEntity::ACTIVITY_IMPORT
        );

        static::assertSame('testfile.csv', $storedFile->getOriginalName());

        $dbFile = $fileRepository->search(new Criteria([$storedFile->getId()]), Context::createDefaultContext())
            ->getEntities()
            ->first();
        static::assertInstanceOf(ImportExportFileEntity::class, $dbFile);
        static::assertSame('testfile.csv', $dbFile->getOriginalName());
    }

    public static function fileTypesProvider(): \Generator
    {
        yield 'CSV file with correct type' => [
            [
                'file' => 'testfile.csv',
                'content' => 'asdf;jkl;wer;\r\n',
                'providedType' => 'text/csv',
                'expectedType' => 'text/csv',
            ],
        ];
        yield 'CSV file with plain type' => [
            [
                'file' => 'testfile.csv',
                'content' => 'asdf;jkl;wer;\r\n',
                'providedType' => 'text/plain',
                'expectedType' => 'text/csv',
            ],
        ];
        yield 'Txt file with plain type' => [
            [
                'file' => 'testfile.txt',
                'content' => 'some text\r\n',
                'providedType' => 'text/plain',
                'expectedType' => 'text/plain',
            ],
        ];
        yield '' => [
            [
                'file' => 'testfile.json',
                'content' => '{}\r\n',
                'providedType' => 'application/json',
                'expectedType' => 'application/json',
            ],
        ];
    }
}
