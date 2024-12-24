<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use ImportExportException::profileWrongType instead
 */
#[Package('services-settings')]
class ProfileWrongTypeException extends CicadaHttpException
{
    public function __construct(
        string $profileId,
        string $profileType
    ) {
        parent::__construct(
            'The import/export profile with id {{ profileId }} can only be used for {{ profileType }}',
            ['profileId' => $profileId, 'profileType' => $profileType]
        );
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::profileWrongType')
        );

        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::profileWrongType')
        );

        return 'CONTENT__IMPORT_EXPORT_PROFILE_WRONG_TYPE';
    }
}
