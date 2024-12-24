<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\Migration;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\Exception\UnknownMigrationSourceException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class MigrationException extends HttpException
{
    final public const FRAMEWORK_MIGRATION_INVALID_VERSION_SELECTION_MODE = 'FRAMEWORK__MIGRATION_INVALID_VERSION_SELECTION_MODE';
    final public const FRAMEWORK_MIGRATION_INVALID_MIGRATION_SOURCE = 'FRAMEWORK__INVALID_MIGRATION_SOURCE';

    public static function invalidVersionSelectionMode(string $mode): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::FRAMEWORK_MIGRATION_INVALID_VERSION_SELECTION_MODE,
            'Version selection mode needs to be one of these values: "{{ validModes }}", but "{{ mode }}" was given.',
            [
                'validModes' => implode('", "', MigrationCollectionLoader::VALID_VERSION_SELECTION_VALUES),
                'mode' => $mode,
            ]
        );
    }

    public static function unknownMigrationSource(string $name): self
    {
        return new UnknownMigrationSourceException($name);
    }
}
