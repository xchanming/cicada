<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class DocumentGenerationException extends CicadaHttpException
{
    public function __construct(string $message = '')
    {
        $message = 'Unable to generate document. ' . $message;
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'DOCUMENT__GENERATION_ERROR';
    }
}
