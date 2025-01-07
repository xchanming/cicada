<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\DocumentType;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class DocumentTypeDetector implements TypeDetectorInterface
{
    protected const SUPPORTED_FILE_EXTENSIONS = [
        'pdf' => [],
        'doc' => [],
        'docx' => [],
        'odt' => [],
    ];

    public function detect(MediaFile $mediaFile, ?MediaType $previouslyDetectedType): ?MediaType
    {
        $fileExtension = mb_strtolower($mediaFile->getFileExtension());
        if (!\array_key_exists($fileExtension, self::SUPPORTED_FILE_EXTENSIONS)) {
            return $previouslyDetectedType;
        }

        if ($previouslyDetectedType === null) {
            $previouslyDetectedType = new DocumentType();
        }

        $previouslyDetectedType->addFlags(self::SUPPORTED_FILE_EXTENSIONS[$fileExtension]);

        return $previouslyDetectedType;
    }
}
