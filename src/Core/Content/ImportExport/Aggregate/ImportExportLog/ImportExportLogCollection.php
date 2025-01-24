<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ImportExportLogEntity>
 */
#[Package('fundamentals@after-sales')]
class ImportExportLogCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'import_export_profile_log_collection';
    }

    protected function getExpectedClass(): string
    {
        return ImportExportLogEntity::class;
    }
}
