<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractPipeFactory
{
    abstract public function create(ImportExportLogEntity $logEntity): AbstractPipe;

    abstract public function supports(ImportExportLogEntity $logEntity): bool;
}
