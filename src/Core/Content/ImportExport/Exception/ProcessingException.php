<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('services-settings')]
class ProcessingException extends CicadaHttpException
{
    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_PROCESSING_EXCEPTION';
    }
}
