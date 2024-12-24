<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Uuid\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class InvalidUuidLengthException extends CicadaHttpException
{
    public function __construct(
        int $length,
        string $hex
    ) {
        parent::__construct(
            'UUID has a invalid length. 16 bytes expected, {{ length }} given. Hexadecimal reprensentation: {{ hex }}',
            ['length' => $length, 'hex' => $hex]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__UUID_INVALID_LENGTH';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
