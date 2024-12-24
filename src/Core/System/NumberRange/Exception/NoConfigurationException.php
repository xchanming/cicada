<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class NoConfigurationException extends CicadaHttpException
{
    public function __construct(
        string $entityName,
        ?string $salesChannelId = null
    ) {
        parent::__construct(
            'No number range configuration found for entity "{{ entity }}" with sales channel "{{ salesChannelId }}".',
            ['entity' => $entityName, 'salesChannelId' => $salesChannelId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__NO_NUMBER_RANGE_CONFIGURATION';
    }
}
