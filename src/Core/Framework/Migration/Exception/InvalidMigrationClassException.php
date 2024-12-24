<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Migration\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class InvalidMigrationClassException extends CicadaHttpException
{
    public function __construct(
        string $class,
        string $path
    ) {
        parent::__construct(
            'Unable to load migration {{ class }} at path {{ path }}',
            ['class' => $class, 'path' => $path]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_MIGRATION';
    }
}
