<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Reader;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
abstract class AbstractReaderFactory
{
    abstract public function create(ImportExportLogEntity $logEntity): AbstractReader;

    abstract public function supports(ImportExportLogEntity $logEntity): bool;
}
