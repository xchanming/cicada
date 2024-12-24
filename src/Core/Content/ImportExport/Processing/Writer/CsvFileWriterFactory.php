<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Writer;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;

#[Package('services-settings')]
class CsvFileWriterFactory extends AbstractWriterFactory
{
    /**
     * @internal
     */
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    public function create(ImportExportLogEntity $logEntity): AbstractWriter
    {
        return new CsvFileWriter($this->filesystem);
    }

    public function supports(ImportExportLogEntity $logEntity): bool
    {
        return $logEntity->getProfile()?->getFileType() === 'text/csv';
    }
}
