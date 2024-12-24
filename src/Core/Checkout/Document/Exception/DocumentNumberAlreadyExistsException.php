<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class DocumentNumberAlreadyExistsException extends CicadaHttpException
{
    public function __construct(?string $number)
    {
        parent::__construct('Document number {{number}} has already been allocated.', [
            'number' => $number,
        ]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'DOCUMENT__NUMBER_ALREADY_EXISTS';
    }
}
