<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class GenerateDocumentActionException extends CicadaHttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'FLOW_BUILDER__DOCUMENT_GENERATION_ERROR';
    }
}
