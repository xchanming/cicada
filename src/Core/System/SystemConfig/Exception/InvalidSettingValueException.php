<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class InvalidSettingValueException extends CicadaHttpException
{
    public function __construct(
        string $key,
        ?string $neededType = null,
        ?string $actualType = null
    ) {
        $message = 'Invalid value for \'{{ key }}\'';
        if ($neededType !== null) {
            $message .= '. Must be of type \'{{ neededType }}\'';
        }
        if ($actualType !== null) {
            $message .= '. But is of type \'{{ actualType }}\'';
        }

        parent::__construct($message, [
            'key' => $key,
            'neededType' => $neededType,
            'actualType' => $actualType,
        ]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM__INVALID_SETTING_VALUE';
    }
}
