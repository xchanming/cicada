<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SystemConfig\SystemConfigException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Exception will be removed
 */
#[Package('services-settings')]
class ConfigurationNotFoundException extends SystemConfigException
{
    public function __construct(string $scope)
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            SystemConfigException::CONFIG_NOT_FOUND,
            'Configuration for scope "{{ scope }}" not found.',
            ['scope' => $scope]
        );
    }
}
