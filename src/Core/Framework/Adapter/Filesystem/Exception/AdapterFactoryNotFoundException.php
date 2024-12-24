<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Filesystem\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class AdapterFactoryNotFoundException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        parent::__construct(
            'Adapter factory for type "{{ type }}" was not found.',
            ['type' => $type]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__FILESYSTEM_ADAPTER_NOT_FOUND';
    }
}
