<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Service;

use Cicada\Core\Content\ImportExport\Processing\Mapping\MappingCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('services-settings')]
abstract class AbstractMappingService
{
    abstract public function getDecorated(): AbstractMappingService;

    abstract public function createTemplate(Context $context, string $profileId): string;

    abstract public function getMappingFromTemplate(
        Context $context,
        UploadedFile $file,
        string $sourceEntity,
        string $delimiter = ';',
        string $enclosure = '"',
        string $escape = '\\'
    ): MappingCollection;
}
