<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Requirement\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
abstract class RequirementException extends CicadaHttpException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_FAILED_DEPENDENCY;
    }
}
