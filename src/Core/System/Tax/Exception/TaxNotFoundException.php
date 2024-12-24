<?php declare(strict_types=1);

namespace Cicada\Core\System\Tax\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class TaxNotFoundException extends CicadaHttpException
{
    public function __construct(string $taxId)
    {
        parent::__construct(
            'Tax with id "{{ id }}" not found.',
            ['id' => $taxId]
        );
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM__TAX_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
