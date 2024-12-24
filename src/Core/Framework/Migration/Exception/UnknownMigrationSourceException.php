<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Migration\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class UnknownMigrationSourceException extends MigrationException
{
    public function __construct(string $name)
    {
        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::FRAMEWORK_MIGRATION_INVALID_MIGRATION_SOURCE,
            'No source registered for "{{ name }}"',
            ['name' => $name]
        );
    }
}
