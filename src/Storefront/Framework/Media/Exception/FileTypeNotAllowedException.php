<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Media\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - Will be removed, use StorefrontFrameworkException::fileTypeNotAllowed instead
 */
#[Package('buyers-experience')]
class FileTypeNotAllowedException extends CicadaHttpException
{
    public function __construct(
        string $mimeType,
        string $uploadType
    ) {
        parent::__construct(
            'Type "{{ mimeType }}" of provided file is not allowed for {{ uploadType }}',
            ['mimeType' => $mimeType, 'uploadType' => $uploadType]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'StorefrontFrameworkException'));

        return 'STOREFRONT__MEDIA_ILLEGAL_FILE_TYPE';
    }
}
