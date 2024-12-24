<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class InvalidDomainException extends CicadaHttpException
{
    public function __construct(string $domain)
    {
        parent::__construct('Invalid domain \'{{ domain }}\'', ['domain' => $domain]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM__INVALID_DOMAIN';
    }
}
