<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Writer;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;

/**
 * @deprecated tag:v6.7.0 - Will be removed in v6.7.0. as it is not used anymore
 */
#[Package('services-settings')]
class XmlFileWriterFactory extends AbstractWriterFactory
{
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __FUNCTION__, 'v6.7.0.0')
        );
    }

    public function create(ImportExportLogEntity $logEntity): AbstractWriter
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __FUNCTION__, 'v6.7.0.0')
        );

        return new XmlFileWriter($this->filesystem);
    }

    public function supports(ImportExportLogEntity $logEntity): bool
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __FUNCTION__, 'v6.7.0.0')
        );

        return $logEntity->getActivity() === ImportExportLogEntity::ACTIVITY_EXPORT
            && $logEntity->getProfile()?->getFileType() === 'text/xml';
    }
}
