<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Service;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Cicada\Core\Content\ImportExport\ImportExportException;
use Cicada\Core\Content\ImportExport\ImportExportProfileEntity;
use Cicada\Core\Content\ImportExport\Processing\Writer\AbstractWriter;
use Cicada\Core\Content\ImportExport\Processing\Writer\CsvFileWriter;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('services-settings')]
class FileService extends AbstractFileService
{
    private readonly CsvFileWriter $writer;

    /**
     * @internal
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly EntityRepository $fileRepository
    ) {
        $this->writer = new CsvFileWriter($filesystem);
    }

    public function getDecorated(): AbstractFileService
    {
        throw new DecorationPatternException(self::class);
    }

    public function storeFile(Context $context, \DateTimeInterface $expireDate, ?string $sourcePath, ?string $originalFileName, string $activity, ?string $path = null): ImportExportFileEntity
    {
        $id = Uuid::randomHex();
        $path ??= $activity . '/' . ImportExportFileEntity::buildPath($id);
        if (!empty($sourcePath)) {
            if (!is_readable($sourcePath)) {
                throw ImportExportException::fileNotReadable($sourcePath);
            }
            $sourceStream = fopen($sourcePath, 'r');
            if (!\is_resource($sourceStream)) {
                throw ImportExportException::fileNotReadable($sourcePath);
            }
            $this->filesystem->writeStream($path, $sourceStream);
        } else {
            $this->filesystem->write($path, '');
        }

        $fileData = [
            'id' => $id,
            'originalName' => $originalFileName,
            'path' => $path,
            'size' => $this->filesystem->fileSize($path),
            'expireDate' => $expireDate,
            'accessToken' => null,
        ];

        $this->fileRepository->create([$fileData], $context);

        $fileEntity = new ImportExportFileEntity();
        $fileEntity->assign($fileData);

        return $fileEntity;
    }

    public function detectType(UploadedFile $file): string
    {
        // TODO: we should do a mime type detection on the file content
        $guessedExtension = $file->guessClientExtension();
        if ($guessedExtension === 'csv' || $file->getClientOriginalExtension() === 'csv') {
            return 'text/csv';
        }

        return $file->getClientMimeType();
    }

    public function getWriter(): AbstractWriter
    {
        return $this->writer;
    }

    public function generateFilename(ImportExportProfileEntity $profile): string
    {
        $extension = $profile->getFileType() === 'text/xml' ? 'xml' : 'csv';
        $timestamp = date('Ymd-His');

        $label = $profile->getTranslation('label');
        \assert(\is_string($label));

        return \sprintf('%s_%s.%s', $label, $timestamp, $extension);
    }

    /**
     * @param array<string, mixed|null> $data
     */
    public function updateFile(Context $context, string $fileId, array $data): void
    {
        $data['id'] = $fileId;
        $this->fileRepository->update([$data], $context);
    }
}
