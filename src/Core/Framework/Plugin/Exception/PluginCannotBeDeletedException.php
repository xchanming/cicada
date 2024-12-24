<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class PluginCannotBeDeletedException extends CicadaHttpException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            'Cannot delete plugin. Error: {{ error }}',
            ['error' => $reason]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_CANNOT_BE_DELETED';
    }
}
