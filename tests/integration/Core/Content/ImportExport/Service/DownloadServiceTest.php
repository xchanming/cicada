<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Service;

use Cicada\Core\Content\ImportExport\Exception\InvalidFileAccessTokenException;
use Cicada\Core\Content\ImportExport\Service\DownloadService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class DownloadServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testUtf8Filename(): void
    {
        $filesystem = $this->getPrivateFilesystem();
        $fileRepository = static::getContainer()->get('import_export_file.repository');

        $asciiName = 'Name with non-ascii chars';

        $fileData = [
            'id' => Uuid::randomHex(),
            'originalName' => $asciiName . ' öäüß',
            'path' => 'test.csv',
            'expireDate' => new \DateTime(),
        ];
        $filesystem->write($fileData['path'], $fileData['originalName']);
        $context = Context::createDefaultContext();
        $fileRepository->create([$fileData], $context);

        $downloadService = new DownloadService($filesystem, $fileRepository);
        $accessToken = $downloadService->regenerateToken($context, $fileData['id']);

        $response = $downloadService->createFileResponse($context, $fileData['id'], $accessToken);
        static::assertIsString($header = $response->headers->get('Content-Disposition'));
        static::assertStringContainsString($asciiName, $header);

        $response->sendContent();
        $this->expectOutputString($fileData['originalName']);
    }

    public function testSlashFilename(): void
    {
        $filesystem = $this->getPrivateFilesystem();
        $fileRepository = static::getContainer()->get('import_export_file.repository');

        $nameWithSlash = 'Name with /\/\/\ slashes';

        $fileData = [
            'id' => Uuid::randomHex(),
            'originalName' => $nameWithSlash,
            'path' => 'test\/.csv',
            'expireDate' => new \DateTime(),
        ];
        $filesystem->write($fileData['path'], $fileData['originalName']);
        $context = Context::createDefaultContext();
        $fileRepository->create([$fileData], $context);

        $downloadService = new DownloadService($filesystem, $fileRepository);
        $accessToken = $downloadService->regenerateToken($context, $fileData['id']);

        $response = $downloadService->createFileResponse($context, $fileData['id'], $accessToken);
        static::assertIsString($header = $response->headers->get('Content-Disposition'));
        static::assertStringNotContainsString($nameWithSlash, $header);
        static::assertStringContainsString('Name with  slashes', $header);
    }

    public function testDownloadWithInvalidAccessToken(): void
    {
        $filesystem = $this->getPrivateFilesystem();
        $fileRepository = static::getContainer()->get('import_export_file.repository');

        $asciiName = 'Name with non-ascii chars';

        $fileData = [
            'id' => Uuid::randomHex(),
            'originalName' => $asciiName . ' öäüß',
            'path' => 'test.csv',
            'expireDate' => new \DateTime(),
            'accessToken' => 'token',
        ];
        $filesystem->write($fileData['path'], $fileData['originalName']);
        $context = Context::createDefaultContext();
        $fileRepository->create([$fileData], $context);

        $downloadService = new DownloadService($filesystem, $fileRepository);

        static::expectException(InvalidFileAccessTokenException::class);

        $downloadService->createFileResponse($context, $fileData['id'], 'token');
    }

    public function testDownloadWithExpiredAccessToken(): void
    {
        $filesystem = $this->getPrivateFilesystem();
        $fileRepository = static::getContainer()->get('import_export_file.repository');

        $asciiName = 'Name with non-ascii chars';

        $fileData = [
            'id' => Uuid::randomHex(),
            'originalName' => $asciiName . ' öäüß',
            'path' => 'test.csv',
            'expireDate' => new \DateTime(),
            'accessToken' => 'token',
        ];
        $filesystem->write($fileData['path'], $fileData['originalName']);
        $context = Context::createDefaultContext();
        $fileRepository->create([$fileData], $context);

        $downloadService = new DownloadService($filesystem, $fileRepository);

        $validToken = $downloadService->regenerateToken($context, $fileData['id']);

        // Expire it
        $connection = static::getContainer()->get(Connection::class);
        $connection->update(
            'import_export_file',
            [
                'updated_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT, strtotime('-6minutes')),
            ],
            [
                'id' => Uuid::fromHexToBytes($fileData['id']),
            ]
        );

        static::expectException(InvalidFileAccessTokenException::class);

        $downloadService->createFileResponse($context, $fileData['id'], $validToken);
    }
}
