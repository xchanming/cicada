<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class InvalidDocumentException extends CicadaHttpException
{
    public function __construct(string $documentId)
    {
        $message = \sprintf('The document with id "%s" is invalid or could not be found.', $documentId);
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'DOCUMENT__INVALID_DOCUMENT_ID';
    }
}
