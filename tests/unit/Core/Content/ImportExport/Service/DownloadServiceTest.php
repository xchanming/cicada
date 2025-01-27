<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\Service;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Cicada\Core\Content\ImportExport\Exception\FileNotFoundException;
use Cicada\Core\Content\ImportExport\Exception\InvalidFileAccessTokenException;
use Cicada\Core\Content\ImportExport\Service\DownloadService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(DownloadService::class)]
class DownloadServiceTest extends TestCase
{
    #[DataProvider('dataProviderInvalidAccessToken')]
    public function testInvalidAccessToken(ImportExportFileEntity $fileEntity, string $accessToken): void
    {
        static::expectException(InvalidFileAccessTokenException::class);
        static::expectExceptionMessage('Access to file denied due to invalid access token');
        /** @var StaticEntityRepository<EntityCollection<ImportExportFileEntity>> $fileRepository */
        $fileRepository = new StaticEntityRepository([new EntityCollection([$fileEntity])]);

        $downloadService = new DownloadService($this->createMock(FilesystemOperator::class), $fileRepository);
        $downloadService->createFileResponse(Context::createDefaultContext(), $fileEntity->getId(), $accessToken);
    }

    #[DataProvider('dataProviderNotFoundFile')]
    public function testNotFoundFile(ImportExportFileEntity $fileEntity, string $accessToken, string $fileId): void
    {
        static::expectException(FileNotFoundException::class);
        static::expectExceptionMessage(\sprintf('Cannot find import/export file with id %s', $fileId));

        /** @var StaticEntityRepository<EntityCollection<ImportExportFileEntity>> $fileRepository */
        $fileRepository = new StaticEntityRepository([new EntityCollection([$fileEntity])]);

        $downloadService = new DownloadService($this->createMock(FilesystemOperator::class), $fileRepository);
        $downloadService->createFileResponse(Context::createDefaultContext(), $fileId, $accessToken);
    }

    #[DataProvider('dataProviderCreateFileResponse')]
    public function testCreateFileResponse(ImportExportFileEntity $fileEntity, string $accessToken, string $fileId, string $expectOutputFilename): void
    {
        /** @var StaticEntityRepository<EntityCollection<ImportExportFileEntity>> $fileRepository */
        $fileRepository = new StaticEntityRepository([new EntityCollection([$fileEntity])]);

        $fileSystem = $this->createMock(FilesystemOperator::class);

        $fileSystem->expects(static::once())->method('readStream')->willReturn(fopen('php://memory', 'r'));
        $fileSystem->expects(static::once())->method('fileSize')->willReturn(100);

        $downloadService = new DownloadService($fileSystem, $fileRepository);
        $response = $downloadService->createFileResponse(Context::createDefaultContext(), $fileId, $accessToken);

        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        static::assertIsString($header = $response->headers->get('Content-Disposition'));
        static::assertStringContainsString($expectOutputFilename, $header);
    }

    /**
     * @return iterable<string, array{fileEntity: ImportExportFileEntity, accessToken: string}>
     */
    public static function dataProviderInvalidAccessToken(): iterable
    {
        yield 'empty access token' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => Uuid::randomHex(),
                'accessToken' => '',
            ]),
            'accessToken' => 'validAccessToken',
        ];

        yield 'mismatched access token' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => Uuid::randomHex(),
                'accessToken' => 'validAccessToken',
            ]),
            'accessToken' => 'inValidAccessToken',
        ];

        yield 'recently modified access token' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => Uuid::randomHex(),
                'accessToken' => 'validAccessToken',
                'updatedAt' => new \DateTimeImmutable('-5 minutes -1 second'),
            ]),
            'accessToken' => 'validAccessToken',
        ];
    }

    /**
     * @return iterable<string, array{fileEntity: ImportExportFileEntity, accessToken: string, fileId: string}>
     */
    public static function dataProviderNotFoundFile(): iterable
    {
        $fileId = Uuid::randomHex();
        $notFoundFileId = Uuid::randomHex();

        yield 'fileId not found' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => $fileId,
                'originalName' => 'fileName',
                'accessToken' => 'validAccessToken',
                'path' => 'path',
                'updatedAt' => new \DateTimeImmutable(),
            ]),
            'accessToken' => 'validAccessToken',
            'fileId' => $notFoundFileId,
        ];

        yield 'file not found' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => $fileId,
                'originalName' => 'fileName',
                'accessToken' => 'validAccessToken',
                'path' => 'path',
                'updatedAt' => new \DateTimeImmutable(),
            ]),
            'accessToken' => 'validAccessToken',
            'fileId' => $fileId,
        ];
    }

    /**
     * @return iterable<string, array{fileEntity: ImportExportFileEntity, accessToken: string, fileId: string, expectOutputFilename: string}>
     */
    public static function dataProviderCreateFileResponse(): iterable
    {
        $fileId = Uuid::randomHex();

        yield 'Name with non-ascii chars' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => $fileId,
                'originalName' => 'Name with öäüß',
                'accessToken' => 'validAccessToken',
                'path' => 'path',
                'updatedAt' => new \DateTimeImmutable(),
            ]),
            'accessToken' => 'validAccessToken',
            'fileId' => $fileId,
            'expectOutputFilename' => 'Name with',
        ];

        yield 'Name with ascii chars' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => $fileId,
                'originalName' => 'Name with ascii chars',
                'accessToken' => 'validAccessToken',
                'path' => 'path',
                'updatedAt' => new \DateTimeImmutable(),
            ]),
            'accessToken' => 'validAccessToken',
            'fileId' => $fileId,
            'expectOutputFilename' => 'Name with',
        ];

        yield 'Name with slashes chars' => [
            'fileEntity' => (new ImportExportFileEntity())->assign([
                'id' => $fileId,
                'originalName' => 'Name with /\/\/\ slashes',
                'accessToken' => 'validAccessToken',
                'path' => 'path',
                'updatedAt' => new \DateTimeImmutable(),
            ]),
            'accessToken' => 'validAccessToken',
            'fileId' => $fileId,
            'expectOutputFilename' => 'Name with  slashes',
        ];
    }
}
