<?php

declare(strict_types=1);

namespace Cicada\Core\Maintenance\System;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed, use Cicada\Core\Maintenance\MaintenanceException instead
 *
 * @phpstan-ignore cicada.internalClass
 */
#[Package('core')]
class SystemException extends HttpException
{
    final public const MAINTENANCE_SYMFONY_CONSOLE_APPLICATION_NOT_FOUND = 'MAINTENANCE__SYMFONY_CONSOLE_APPLICATION_NOT_FOUND';

    final public const MAINTENANCE_MIGRATION_INVALID_VERSION_SELECTION_MODE = 'MAINTENANCE__MIGRATION_INVALID_VERSION_SELECTION_MODE';

    public static function consoleApplicationNotFound(): self
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(__CLASS__, 'v6.7.0.0', 'MaintenanceException::consoleApplicationNotFound')
        );

        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::MAINTENANCE_SYMFONY_CONSOLE_APPLICATION_NOT_FOUND,
            'Symfony console application not found'
        );
    }

    public static function invalidVersionSelectionMode(string $mode): self
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(__CLASS__, 'v6.7.0.0', 'MaintenanceException::invalidVersionSelectionMode')
        );

        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::MAINTENANCE_MIGRATION_INVALID_VERSION_SELECTION_MODE,
            'Version selection mode needs to be one of these values: "{{ validModes }}", but "{{ mode }}" was given.',
            [
                'validModes' => implode('", "', MigrationCollectionLoader::VALID_VERSION_SELECTION_VALUES),
                'mode' => $mode,
            ]
        );
    }
}
