<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class InvalidDocumentRendererException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        $message = \sprintf('Unable to find a document renderer with type "%s"', $type);
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'DOCUMENT__INVALID_RENDERER_TYPE';
    }
}
