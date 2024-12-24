<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ImportExportProfileTranslationEntity>
 */
#[Package('services-settings')]
class ImportExportProfileTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ImportExportProfileTranslationEntity::class;
    }
}
