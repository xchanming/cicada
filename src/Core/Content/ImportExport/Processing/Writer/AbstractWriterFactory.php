<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Writer;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractWriterFactory
{
    abstract public function create(ImportExportLogEntity $logEntity): AbstractWriter;

    abstract public function supports(ImportExportLogEntity $logEntity): bool;
}
