<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ExpectationFailedException extends CicadaHttpException
{
    /**
     * @param array<string> $fails
     */
    public function __construct(private readonly array $fails)
    {
        parent::__construct('API Expectations failed', []);
    }

    /**
     * @return array<string> $failedExpectations
     */
    public function getParameters(): array
    {
        return $this->fails;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__API_EXPECTATION_FAILED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_EXPECTATION_FAILED;
    }
}
