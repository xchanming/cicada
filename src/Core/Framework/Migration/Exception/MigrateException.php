<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Migration\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class MigrateException extends CicadaHttpException
{
    public function __construct(
        string $message,
        \Exception $previous
    ) {
        parent::__construct('Migration error: {{ errorMessage }}', ['errorMessage' => $message], $previous);
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__MIGRATION_ERROR';
    }
}
